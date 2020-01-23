<?php
namespace App\Components;

use App\Components\Cucm\Models\RedirectedPhone;

class CucmAxl
{
    private const SQL = [
        'phones' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE d.tkclass = 1 AND  d.tkmodel != 72',
        'phone' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE UPPER(d.name) LIKE UPPER(',
        'phone_name' => 'SELECT name FROM device WHERE UPPER(name) LIKE UPPER(',
        'phones_names' => 'SELECT d.name FROM device d WHERE d.tkclass = 1 AND  d.tkmodel != 72',
        'redirectedPhones' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as phprefix, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool left JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" left JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1 and (cfd.cfadestination !="" or n.cfbintdestination  !="" or n.cfbdestination  !="" or n.cfnaintdestination !="" or n.cfnadestination !="" or n.cfurintdestination  !="" or n.cfurdestination  !="")',
        'redirectedPhonesWithPrefixes558or559' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool  WHERE dmap.numplanindex = 1 and (cfd.cfadestination !="" or n.cfbintdestination  !="" or n.cfbdestination  !="" or n.cfnaintdestination !="" or n.cfnadestination !="" or n.cfurintdestination  !="" or n.cfurdestination  !="")',
        'redirectedPhonesWithCallForwardingNumber' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as phprefix, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool left JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" left JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1 and ',
        'redirectedPhonesWithCallForwardingNumberWithPrefixes558or559' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool  WHERE dmap.numplanindex = 1 and ',
    ];

    private const CUCM_PREFIX_MAP = [
        '10.30.48.10' => '551',
        '10.30.30.70' => '558',
        '10.30.30.21' => '559',
    ];
    private $axlClient;
    private $ip;

    /**
     * CucmAxl constructor.
     * @param string $ip
     * @param CucmAxlClient $axlClient
     */
    public function __construct(string $ip, CucmAxlClient $axlClient)
    {
        $this->axlClient = $axlClient;
        $this->ip = (new IpTools($ip))->address;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function phones(): array
    {
        $phones = $this->connection()->ExecuteSQLQuery(['sql' => self::SQL['phones']])->return->row;
        if (is_null($phones)) {
            $phones = [];
        }
        $result = [];
        foreach ($phones as $phone) {
            $phone->prefix = $this->validPrefix($phone->prefix);
            $result[mb_strtoupper($phone->name)] = get_object_vars($phone);
        }
        return $result;
    }

    /**
     * @param string $name
     * @return array|bool
     * @throws \Exception
     */
    public function phoneWithName(string $name)
    {
        if (!$this->isExistedPhone($name)) {
            return false;
        }
        $phone = $this->connection()->ExecuteSQLQuery(['sql' => self::SQL['phone'].'\''.$name.'\')'])->return->row;
        $phone->prefix = $this->validPrefix($phone->prefix);
        return get_object_vars($phone);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function phonesNames(): array
    {
        return array_map(
            function ($phone) {
                return $phone->name;
            },
            $this->connection()->ExecuteSQLQuery(['sql' => self::SQL['phones_names']])->return->row
        );
    }

    /**
     * @return string Cucm's schema
     * @throws \Exception
     */
    public function schema(): string
    {
        return $this->axlClient->schema();
    }

    /**
     * Redirected Phones
     * @return array Redirected Phones
     * @throws \Exception
     */
    public function redirectedPhones(): array
    {
        $phones =
            (array_key_exists($this->ip, self::CUCM_PREFIX_MAP)
                && ("558" == self::CUCM_PREFIX_MAP[$this->ip] || "559" == self::CUCM_PREFIX_MAP[$this->ip])
            )
                ? ($this->connection()->ExecuteSQLQuery(['sql' => self::SQL['redirectedPhonesWithPrefixes558or559']])->return->row ?? [])
                : ($this->connection()->ExecuteSQLQuery(['sql' => self::SQL['redirectedPhones']])->return->row ?? []);
        return $this->castToRedirectedPhonesResult($phones);
    }

    /**
     * Redirected Phones With Call Forwarding Number
     * @param string $callForwardingNumber
     * @return array Redirected Phones With Call Forwarding Number
     * @throws \Exception
     */
    public function redirectedPhonesWithCallForwardingNumber(string $callForwardingNumber): array
    {
        $callForwardingNumberCondition =
            '(cfd.cfadestination ="' . $callForwardingNumber
            . '" or n.cfbintdestination  ="' . $callForwardingNumber
            . '" or n.cfbdestination  ="' . $callForwardingNumber
            . '" or n.cfnaintdestination ="' . $callForwardingNumber
            . '" or n.cfnadestination ="' . $callForwardingNumber
            . '" or n.cfurintdestination  ="' . $callForwardingNumber
            . '" or n.cfurdestination  ="' . $callForwardingNumber.'")'
        ;
        $query =
            (array_key_exists($this->ip, self::CUCM_PREFIX_MAP)
                && ("558" == self::CUCM_PREFIX_MAP[$this->ip] || "559" == self::CUCM_PREFIX_MAP[$this->ip])
            )
                ? self::SQL['redirectedPhonesWithCallForwardingNumberWithPrefixes558or559'] . $callForwardingNumberCondition
                : self::SQL['redirectedPhonesWithCallForwardingNumber'] . $callForwardingNumberCondition;
        $phones = $this->connection()->ExecuteSQLQuery(['sql' => $query])->return->row ?? [];
        return $this->castToRedirectedPhonesResult($phones);
    }

    /**
     * @param $redirectedPhones
     * @return array Redirected Phones
     * @throws \T4\Core\MultiException
     */
    private function castToRedirectedPhonesResult($redirectedPhones): array
    {
        $result = [];
        if (!is_array($redirectedPhones)) {
            $redirectedPhones = [$redirectedPhones];
        }
        foreach ($redirectedPhones as $phone) {
            $phone = (new RedirectedPhone())->fill($phone);
            $phone->cucm = $this->cucm();
            $phone->lastUpdate =
                (new \DateTime('now',
                    new \DateTimeZone('UTC')
                ))->format('Y-m-d H:i:s P');
            $result[] = $phone;
        }
        return $result;
    }

    /**
     * @return string Cucm's IP
     */
    private function cucm(): string
    {
        return $this->ip;
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function validPrefix(string $prefix): string
    {
        if ($this->isManuallyAssignedPrefix()) {
            return self::CUCM_PREFIX_MAP[$this->ip];
        }
        return mb_ereg_replace('\..+', '', $prefix);
    }

    /**
     * @return bool
     */
    private function isManuallyAssignedPrefix(): bool
    {
        return array_key_exists($this->ip, self::CUCM_PREFIX_MAP);
    }

    /**
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    private function isExistedPhone(string $name): bool
    {
        $phoneName = $this->connection()->ExecuteSQLQuery(['sql' => self::SQL['phone_name'].'\''.$name.'\')'])->return->row;
        return is_null($phoneName) ? false : true;
    }

    /**
     * @return \SoapClient
     * @throws \Exception
     */
    private function connection(): \SoapClient
    {
        return $this->axlClient->client();
    }
}
