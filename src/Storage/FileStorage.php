<?php
namespace Nopolabs\Yabot\Storage;

class FileStorage implements StorageInterface
{
    private $path;

    public function __construct($path = '')
    {
        $this->path = $path;
    }

    protected function getFilename($key)
    {
        return $this->path.DIRECTORY_SEPARATOR.$key.'.json';
    }

    public function save($key, $data)
    {
        $file = $this->getFilename($key);
        $this->putFile($file, $data);
    }

    public function get($key)
    {
        $file = $this->getFilename($key);
        return $this->getFile($file);
    }

    public function delete($key)
    {
        $file = $this->getFilename($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function all()
    {
        $files = glob($this->path.'/*.json');
        $data = [];
        foreach ($files as $file) {
            $data[] = $this->getFile($file);
        }

        return $data;
    }

    protected function putFile($file, $data)
    {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, json_encode($data));
    }

    protected function getFile($file)
    {
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        } else {
            return [];
        }
    }
}