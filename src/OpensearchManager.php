<?php
namespace Qmrp\Opensearch;

use Qmrp\Opensearch\Factories\OpensearchFactory;
use GrahamCampbell\Manager\AbstractManager;
use Illuminate\Contracts\Config\Repository;

class OpensearchManager extends AbstractManager
{
    protected $factory;
    
    public function __construct(Repository $config, OpensearchFactory $factory)
    {
        parent::__construct($config);
        $this->factory = $factory;
    }
    
    protected function createConnection(array $config)
    {
        return $this->factory->make($config);
    }
  
    protected function getConfigName()
    {
        return 'opensearch';
    }
   
    public function getFactory()
    {
        return $this->factory;
    }
}