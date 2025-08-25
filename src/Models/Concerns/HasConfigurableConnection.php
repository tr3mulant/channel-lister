<?php

namespace IGE\ChannelLister\Models\Concerns;

use IGE\ChannelLister\Data\DefaultFieldDefinitions;

trait HasConfigurableConnection
{
    /**
     * Get the database connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return DefaultFieldDefinitions::getDatabaseConnection() !== null && DefaultFieldDefinitions::getDatabaseConnection() !== '' && DefaultFieldDefinitions::getDatabaseConnection() !== '0' ? DefaultFieldDefinitions::getDatabaseConnection() : $this->connection;
    }
}
