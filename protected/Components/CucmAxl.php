<?php
namespace App\Components;

class CucmAxl
{
    private const SQL = [
        'phones' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE d.tkclass = 1 AND  d.tkmodel != 72',
        'phone' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE UPPER(d.name) LIKE UPPER(',
        'phone_name' => 'SELECT name FROM device WHERE UPPER(name) LIKE UPPER(',
        'phones_names' => 'SELECT d.name FROM device d WHERE d.tkclass = 1 AND  d.tkmodel != 72',
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
        if (!$this->isPhoneExists($name)) {
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
     * @return string
     * @throws \Exception
     */
    public function schema(): string
    {
        return $this->axlClient->schema();
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function validPrefix(string $prefix): string
    {
        if ($this->isPrefixAssignManually()) {
            return self::CUCM_PREFIX_MAP[$this->ip];
        }
        return mb_ereg_replace('\..+', '', $prefix);
    }

    /**
     * @return bool
     */
    private function isPrefixAssignManually(): bool
    {
        return array_key_exists($this->ip, self::CUCM_PREFIX_MAP);
    }

    /**
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    private function isPhoneExists(string $name): bool
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
