<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 */

namespace Codeacious\Orm\DBAL\Types;

use Codeacious\Model\AbstractEntity;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use DateTime;
use DateTimeZone;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to a PHP DateTime object.
 * 
 * Dates in other timezones are converted to UTC for storage. PHP strings are automatically
 * converted to DateTime objects, if they are in RFC 3339 format.
 */
class DateTimeType extends \Doctrine\DBAL\Types\DateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null)
            return null;
        
        $object = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value,
            self::getStorageTimezone());
        
        if (!$object)
        {
            throw ConversionException::conversionFailedFormat($value, $this->getName(),
                $platform->getDateTimeFormatString());
        }
        
        return $object;
    }
    
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null)
            return null;
        
        //Attempt to parse strings
        if (is_string($value))
            $value = AbstractEntity::stringToDate($value);
        
        //Check type
        if (! ($value instanceof DateTime))
        {
            throw new DBALException($this->getName().' fields expect a PHP DateTime object or an '
                .'RFC 3339 date string');
        }
        
        //Convert to UTC and format for database
        $storageDate = new DateTime();
        $storageDate->setTimezone(self::getStorageTimezone());
        $storageDate->setTimestamp($value->getTimestamp());
        return $storageDate->format($platform->getDateTimeFormatString());
    }
    
    /**
     * @return DateTimeZone
     */
    private static function getStorageTimezone()
    {
        static $tz;
        if (!$tz)
            $tz = new DateTimeZone('UTC');
        
        return $tz;
    }
}
