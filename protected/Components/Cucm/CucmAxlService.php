<?php
namespace App\Components\Cucm;

use App\Components\Cucm\Models\RedirectedPhone;

class CucmAxlService
{
    private const SQL = [
        'phones' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE d.tkclass = 1 AND  d.tkmodel != 72',
        'phone' => 'SELECT d.name, d.description, css.name css, tm.name model, dp.name devicepool, n.dnorpattern phonedn, n.alertingname alertingName, pt.name partition, (SELECT TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) FROM device d2 INNER JOIN devicenumplanmap dmap2 ON dmap2.fkdevice = d2.pkid and dmap2.numplanindex = 1 INNER JOIN numplan n1 ON n1.pkid = dmap2.fknumplan INNER JOIN routepartition pt2 ON pt2.pkid = n1.fkroutepartition INNER JOIN callingsearchspace css2 ON css2.clause LIKE "%" || pt2.name || "%" INNER JOIN numplan n2 ON n2.fkcallingsearchspace_translation = css2.pkid AND n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) WHERE d2.name = d.name) prefix FROM device d LEFT JOIN callingsearchspace css ON css.pkid = d.fkcallingsearchspace LEFT JOIN typemodel tm ON tm.enum = d.tkmodel LEFT JOIN DevicePool dp ON dp.pkid = d.fkDevicePool LEFT JOIN devicenumplanmap dmap ON dmap.fkdevice = d.pkid AND dmap.numplanindex = 1 LEFT JOIN numplan n ON n.pkid = dmap.fknumplan LEFT JOIN routepartition pt ON pt.pkid = n.fkroutepartition WHERE UPPER(d.name)',
        'redirectedPhones' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as phprefix, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool left JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" left JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1 and (cfd.cfadestination !="" or n.cfbintdestination  !="" or n.cfbdestination  !="" or n.cfnaintdestination !="" or n.cfnadestination !="" or n.cfurintdestination  !="" or n.cfurdestination  !="")',
        'redirectedPhonesWithPrefixes558or559' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool  WHERE dmap.numplanindex = 1 and (cfd.cfadestination !="" or n.cfbintdestination  !="" or n.cfbdestination  !="" or n.cfnaintdestination !="" or n.cfnadestination !="" or n.cfurintdestination  !="" or n.cfurdestination  !="")',
        'redirectedPhonesWithCallForwardingNumber' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as phprefix, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool left JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" left JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1',
        'redirectedPhonesWithCallForwardingNumberWithPrefixes558or559' => 'SELECT d.name as Device, d.description AS depiction, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName,cfd.cfadestination as ForwardAll, cfd.cfavoicemailenabled as Forward_All_Mail,n.cfbintdestination as ForwardBusyInternal ,n.cfbdestination as ForwardBusyExternal,n.cfnaintdestination as Forward_no_Answer_Internal,n.cfnadestination as Forward_no_Answer_External,n.cfurintdestination as Forward_Unregistred_internal,n.cfurdestination as Forward_Unregistred_External,n.cfnaduration,tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid left JOIN numplan AS n ON dmap.fknumplan = n.pkid inner join callforwarddynamic as cfd on cfd.fknumplan=n.pkid left JOIN typemodel AS tm ON d.tkmodel = tm.enum left JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool  WHERE dmap.numplanindex = 1',
    ];
    private const CUCM_PREFIX_MAP = [
        '10.30.48.10' => '551',
        '10.30.30.70' => '558',
        '10.30.30.21' => '559',
    ];
    private $cucm;
    private $client;

    /**
     * CucmAxlService constructor.
     * @param Cucm $cucm
     */
    public function __construct(Cucm $cucm)
    {
        $this->cucm = $cucm;
    }

    /**
     * Data of the phone with given name
     * @param string $name
     * @return \stdClass|false
     * @throws \SoapFault
     */
    public function phone(string $name)
    {
        $phone = false;
        $response = $this->executeSQLQuery(self::SQL['phone'] . 'LIKE UPPER(\'' . $name . '\')');
        if (empty($response)) {
            return $phone;
        }
        $phone = reset($response);
        if ($this->isManuallyAssignedPrefix()) {
            $phone->prefix = $this->validPrefix();
        }
        return $phone;
    }

    /**
     * Data of the phones
     * @return array of \stdClass or []
     * @throws \SoapFault
     */
    public function phones(): array
    {
        $phones = [];
        $response = $this->executeSQLQuery(self::SQL['phones']);
        if (empty($response)) {
            return $phones;
        }
        array_walk(
            $response,
            function ($phone) use (&$phones) {
                if ($this->isManuallyAssignedPrefix()) {
                    $phone->prefix = $this->validPrefix();
                }
                $phones[mb_strtoupper($phone->name)] = $phone;
            }
        );
        return $phones;
    }

    /**
     * Original command of Axl service
     * @param string $name
     * @return \stdClass|false
     * @throws \SoapFault
     */
    public function cmdGetPhone(string $name)
    {
        try {
            $responce = $this->client()->getPhone(['name' => $name]);
        } catch (\SoapFault $e) {
            if (mb_ereg_match('.+not found.*', $e->getMessage())) {
                return false;
            }
            throw $e;
        }
        return $responce->return->phone;
    }

    /**
     * Redirected Phones
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault
     */
    public function redirectedPhones(): array
    {
        $response = $this->isPrefix558or559()
            ? $this->executeSQLQuery(self::SQL['redirectedPhonesWithPrefixes558or559'])
            : $this->executeSQLQuery(self::SQL['redirectedPhones']);
        ;
        return $this->typeRedirectedPhoneResponse($response);
    }

    /**
     * Redirected Phones with callForwardingNumber
     * @param string $callForwardingNumber
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault
     */
    public function redirectedPhonesWithCallForwardingNumber(string $callForwardingNumber): array
    {
        $callForwardingNumberCondition =
            ' AND (cfd.cfadestination ="' . $callForwardingNumber
            . '" OR n.cfbintdestination  ="' . $callForwardingNumber
            . '" OR n.cfbdestination  ="' . $callForwardingNumber
            . '" OR n.cfnaintdestination ="' . $callForwardingNumber
            . '" OR n.cfnadestination ="' . $callForwardingNumber
            . '" OR n.cfurintdestination  ="' . $callForwardingNumber
            . '" OR n.cfurdestination  ="' . $callForwardingNumber.'")'
        ;
        $response = $this->isPrefix558or559()
            ? $this->executeSQLQuery(self::SQL['redirectedPhonesWithCallForwardingNumberWithPrefixes558or559'] . $callForwardingNumberCondition)
            : $this->executeSQLQuery(self::SQL['redirectedPhonesWithCallForwardingNumber'] . $callForwardingNumberCondition)
        ;
        return $this->typeRedirectedPhoneResponse($response);
    }

    /**
     * Redirected Phones containing callForwardingNumber as substring
     * @param string $callForwardingNumber
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault
     */
    public function redirectedPhonesContainingCallForwardingNumberAsSubstring(string $callForwardingNumber): array
    {
        $callForwardingNumberCondition =
            ' AND (cfd.cfadestination LIKE "%' . $callForwardingNumber
            . '%" OR n.cfbintdestination  LIKE "%' . $callForwardingNumber
            . '%" OR n.cfbdestination  LIKE "%' . $callForwardingNumber
            . '%" OR n.cfnaintdestination LIKE "%' . $callForwardingNumber
            . '%" OR n.cfnadestination LIKE "%' . $callForwardingNumber
            . '%" OR n.cfurintdestination  LIKE "%' . $callForwardingNumber
            . '%" OR n.cfurdestination  LIKE "%' . $callForwardingNumber.'%")'
        ;
        $response = $this->isPrefix558or559()
            ? $this->executeSQLQuery(self::SQL['redirectedPhonesWithCallForwardingNumberWithPrefixes558or559'] . $callForwardingNumberCondition)
            : $this->executeSQLQuery(self::SQL['redirectedPhonesWithCallForwardingNumber'] . $callForwardingNumberCondition)
        ;
        return $this->typeRedirectedPhoneResponse($response);
    }

    /**
     * @return \SoapClient
     * @throws \SoapFault
     */
    private function client(): \SoapClient
    {
        if (is_null($this->client)) {
            ini_set('default_socket_timeout', '300');
            $wsdl = realpath(ROOT_PATH . '/AXLscheme/' . $this->cucm->schema() . '/AXLAPI.wsdl');
            $this->client = new \SoapClient(
                $wsdl,
                [
                    'trace' => true,
                    'exception' => true,
                    'login' => $this->cucm->login(),
                    'password' => $this->cucm->password(),
                    'keep_alive' => true,
                    'stream_context' => $this->streamContext(),
                    'location' => 'https://' . $this->cucm->ip() . ':8443/axl',
                ]
            );
        }
        return $this->client;
    }

    /**
     * @return resource
     */
    private function streamContext()
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'AES256-SHA',
            ]
        ]);
    }

    /**
     * @return bool
     */
    private function isManuallyAssignedPrefix(): bool
    {
        return array_key_exists($this->cucm->ip(), self::CUCM_PREFIX_MAP);
    }

    /**
     * @return bool
     */
    private function isPrefix558or559(): bool
    {
        return array_key_exists($this->cucm->ip(), self::CUCM_PREFIX_MAP) &&
            ('558' == self::CUCM_PREFIX_MAP[$this->cucm->ip()] || '559' == self::CUCM_PREFIX_MAP[$this->cucm->ip()]);
    }

    /**
     * @return string
     */
    private function validPrefix(): string
    {
        return self::CUCM_PREFIX_MAP[$this->cucm->ip()];
    }

    /**
     * Execute SQL Query
     * @param string $sql
     * @return array
     * @throws \SoapFault
     */
    private function executeSQLQuery(string $sql)
    {
        $response = $this->client()->ExecuteSQLQuery(['sql' => $sql])->return;
        if (!isset($response->row)) {
            return [];
        }
        return is_array($response->row) ? $response->row : [$response->row];
    }

    /**
     * @param array $response
     * @return array of RedirectedPhone|[]
     */
    private function typeRedirectedPhoneResponse(array $response): array
    {
        $redirectedPhones = [];
        if (empty($response)) {
            return $redirectedPhones;
        }
        array_walk(
            $response,
            function ($dataOfRedirectedPhone) use (&$redirectedPhones) {
                $dataOfRedirectedPhone->cucm = $this->cucm->ip();
                if ($this->isManuallyAssignedPrefix()) {
                    $dataOfRedirectedPhone->phprefix = $this->validPrefix();
                }
                $dataOfRedirectedPhone->lastUpdate =
                    (new \DateTime('now',
                        new \DateTimeZone('UTC')
                    ))->format('Y-m-d H:i:s P');
                $redirectedPhones[] = (new RedirectedPhone())->fill($dataOfRedirectedPhone);
            }
        );
        return $redirectedPhones;
    }
}
