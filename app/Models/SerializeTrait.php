<?php
namespace App\Models;

trait SerializeTrait {

    public function toArray()
    {
        $array = parent::toArray();

        $filtered = [];
        foreach ($array as $key => $value) {
            if (in_array($key, $this->private)) continue;
            $filtered[$key] = $value;
        }

        return $filtered;
    }

}