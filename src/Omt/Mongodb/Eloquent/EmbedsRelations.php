<?php

namespace Omt\Mongodb\Eloquent;

use Illuminate\Support\Str;
use Omt\Mongodb\Relations\EmbedsMany;
use Omt\Mongodb\Relations\EmbedsOne;

trait EmbedsRelations
{
    /**
     * Define an embedded one-to-many relationship.
     * @param string $related
     * @param string $localKey
     * @param string $foreignKey
     * @param string $relation
     * @return \Omt\Mongodb\Relations\EmbedsMany
     */
    protected function embedsMany($related, $localKey = null, $foreignKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);

            $relation = $caller['function'];
        }

        if ($localKey === null) {
            $localKey = $relation;
        }

        if ($foreignKey === null) {
            $foreignKey = Str::snake(class_basename($this));
        }

        $query = $this->newQuery();

        $instance = new $related;

        return new EmbedsMany($query, $this, $instance, $localKey, $foreignKey, $relation);
    }

    /**
     * Define an embedded one-to-many relationship.
     * @param string $related
     * @param string $localKey
     * @param string $foreignKey
     * @param string $relation
     * @return \Omt\Mongodb\Relations\EmbedsOne
     */
    protected function embedsOne($related, $localKey = null, $foreignKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if ($relation === null) {
            list(, $caller) = debug_backtrace(false);

            $relation = $caller['function'];
        }

        if ($localKey === null) {
            $localKey = $relation;
        }

        if ($foreignKey === null) {
            $foreignKey = Str::snake(class_basename($this));
        }

        $query = $this->newQuery();

        $instance = new $related;

        return new EmbedsOne($query, $this, $instance, $localKey, $foreignKey, $relation);
    }
}
