<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\Model;

use Codeacious\Model\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * An arbitrary configuration property, stored as a key-value pair.
 *
 * @ORM\Entity
 * @ORM\Table(name="configuration_item")
 *
 * @method string getKey
 * @method $this setKey(string $value)
 *
 * @method string getValue
 * @method $this setValue(string $value)
 */
class ConfigurationItem extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(name="configKey", length=150)
     * @ORM\Id
     */
    protected $key;

    /**
     * @var string
     * @ORM\Column(name="configValue", length=1024, nullable=true)
     */
    protected $value;


    /**
     * @param string $key
     * @param string $value
     */
    public function __construct($key, $value=null)
    {
        parent::__construct();
        $this->key = $key;
        $this->value = $value;
    }
}