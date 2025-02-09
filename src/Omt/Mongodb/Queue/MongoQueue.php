<?php

namespace Omt\Mongodb\Queue;

use Carbon\Carbon;
use Illuminate\Queue\DatabaseQueue;
use Omt\Mongodb\Connection;
use MongoDB\Operation\FindOneAndUpdate;

class MongoQueue extends DatabaseQueue
{
    /**
     * The expiration time of a job.
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * The connection name for the queue.
     * @var string
     */
    protected $connectionName;

    /**
     * @inheritdoc
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        parent::__construct($database, $table, $default, $retryAfter);
        $this->retryAfter = $retryAfter;
    }

    /**
     * @inheritdoc
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if ($this->retryAfter !== null) {
            $this->releaseJobsThatHaveBeenReservedTooLong($queue);
        }

        if ($job = $this->getNextAvailableJobAndReserve($queue)) {
            return new MongoJob(
                $this->container, $this, $job, $this->connectionName, $queue
            );
        }
    }

    /**
     * Get the next available job for the queue and mark it as reserved.
     * When using multiple daemon queue listeners to process jobs there
     * is a possibility that multiple processes can end up reading the
     * same record before one has flagged it as reserved.
     * This race condition can result in random jobs being run more then
     * once. To solve this we use findOneAndUpdate to lock the next jobs
     * record while flagging it as reserved at the same time.
     * @param string|null $queue
     * @return \StdClass|null
     */
    protected function getNextAvailableJobAndReserve($queue)
    {
        $job = $this->database->getCollection($this->table)->findOneAndUpdate(
            [
                'queue' => $this->getQueue($queue),
                'reserved' => ['$ne' => 1],
                'available_at' => ['$lte' => Carbon::now()->getTimestamp()],
            ],
            [
                '$set' => [
                    'reserved' => 1,
                    'reserved_at' => Carbon::now()->getTimestamp(),
                ],
                '$inc' => [
                    'attempts' => 1,
                ],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                'sort' => ['available_at' => 1],
            ]
        );

        if ($job) {
            $job->id = $job->_id;
        }

        return $job;
    }

    /**
     * Release the jobs that have been reserved for too long.
     * @param string $queue
     * @return void
     */
    protected function releaseJobsThatHaveBeenReservedTooLong($queue)
    {
        $expiration = Carbon::now()->subSeconds($this->retryAfter)->getTimestamp();

        $reserved = $this->database->collection($this->table)
            ->where('queue', $this->getQueue($queue))
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<=', $expiration)
            ->get();

        foreach ($reserved as $job) {
            $this->releaseJob($job['_id'], $job['attempts']);
        }
    }

    /**
     * Release the given job ID from reservation.
     * @param string $id
     * @param int $attempts
     * @return void
     */
    protected function releaseJob($id, $attempts)
    {
        $this->database->table($this->table)->where('_id', $id)->update([
            'reserved' => 0,
            'reserved_at' => null,
            'attempts' => $attempts,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->collection($this->table)->where('_id', $id)->delete();
    }
}
