<?php

namespace FFraenz\PrivateComposerInstaller\Resolver;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Loader\Loader;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Store\StoreBuilder;

class Dotenv4Resolver implements ResolverInterface
{
    /**
     * @var ArrayAdapter
     */
    protected $dotenvAdapter;

    /**
     * @var PutenvAdapter
     */
    protected $putenvAdapter;

    /**
     * @var string
     */
    protected $dotenvPath;

    /**
     * @var string
     */
    protected $dotenvName;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dotenvPath = getcwd();
        $this->dotenvName = '.env';
    }

    /**
     * Lazily create a PutenvAdapter instance providing environment variables.
     * @return PutenvAdapter
     */
    protected function getPutenvAdapter(): PutenvAdapter
    {
        if ($this->putenvAdapter === null) {
            $this->putenvAdapter = new PutenvAdapter();
        }
        return $this->putenvAdapter;
    }

    /**
     * Lazily create an ArrayAdapter instance providing .env file variables.
     * @return ArrayAdapter
     */
    protected function getDotenvAdapter(): ArrayAdapter
    {
        if ($this->dotenvAdapter === null) {
            $this->dotenvAdapter = new ArrayAdapter();

            try {
                $repository = RepositoryBuilder::create()
                    ->withReaders([])
                    ->withWriters([$this->dotenvAdapter])
                    ->make();

                $fileStore = StoreBuilder::create()
                    ->withPaths([$this->dotenvPath])
                    ->withNames([$this->dotenvName])
                    ->make();

                $loader = new Loader();
                $loader->load($repository, $fileStore->read());
            } catch (InvalidPathException $e) {
                // Environment variables could not be loaded
                // Continue with empty array adapter
            }
        }
        return $this->dotenvAdapter;
    }

    /**
     * Resolve the given key to an environment value.
     * @param string $key Environment key
     * @return mixed|null Environment value or null, if not available
     */
    public function get(string $key)
    {
        return $this->getPutenvAdapter()->get($key)
            ->getOrCall(function () use ($key) {
                return $this->getDotenvAdapter()->get($key)
                    ->getOrCall(function () use ($key) {
                        return null;
                    });
            });
    }
}
