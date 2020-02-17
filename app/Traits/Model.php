<?php
namespace BRM\Vivid\app\Traits;

trait Model
{
    public function getConnectionName()
    {

      if (class_exists('\BRM\Tenants\FrameworkServiceProvider')) {
        return app(\Hyn\Tenancy\Database\Connection::class)->tenantName();
      }
      return parent::getConnectionName();
    }
}