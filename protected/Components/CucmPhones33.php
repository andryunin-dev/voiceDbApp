<?php
namespace App\Components;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class CucmPhones33 extends Std
{
    protected $axlClient;
    protected $risPortClient;
    protected $publisherIP;

    public function __construct($ip)
    {
        // Common client's options
        $publisherIP = (new IpTools($ip))->address;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'HIGH',
            ]
        ]);
        $username = 'netcmdbAXL';
        $password = 'Dth.dAXL71';
        $schema = 'sch7_1';

        // AXL client
        $this->axlClient = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $schema . '/AXLAPI.wsdl'), [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $publisherIP . ':8443/axl',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);

        // RisPort client
        $this->risPortClient = new \SoapClient('https://' . $publisherIP . ':8443/realtimeservice/services/RisPort?wsdl', [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);

        $this->publisherIP = $publisherIP;
    }

    /**
     * @return bool
     */
    public function run()
    {
        $phones = new Collection();

//        $allPhones =  $this->axlClient->ListPhoneByName(['searchString' => '%'])->return->phone;
//        foreach ($allPhones as $item) {
//            $phone = (new Std())->fill([
//                'name' => $item->name, // SEP000AB8934234
//                'model' => $item->model, // Cisco 7912
//                'uuid' => $item->uuid, // {F4701BB1-C804-42BB-868A-014E511AF398}
//            ]);
//            $phones->add($phone);
//        }
////        var_dump($phones);
//
//        foreach ($phones as $phone) {
//            $item = $this->axlClient->GetPhone(['phoneName' => $phone->name])->return->device;
////            var_dump($item);
//            $phone->fill([
//                'description' => $item->description, // Lenina52-UKP_FO-1406
//                'callingSearchSpaceName' => $item->callingSearchSpaceName, // CSS_HQ_50_City
//                'dirn_uuid' => $item->lines->line->dirn->uuid, // {609733DC-147A-4B69-BA1E-C3B25274961C}
//            ]);
//        }
////        var_dump($phones);

        $allCssNames = $this->axlClient->ListCSSByName(['searchString' => '%']);
        var_dump($allCssNames);


        $routePlans = $this->axlClient->ListRoutePlanByType([
            'usage' => 'Device',
        ])->return->routePlan;
//        var_dump($routePlans);


        // Найти callingSearchSpaceNames for routePlans с directoryNumber из 8 символов и начинающихся на 5
        $translationPatterns = new Collection();
        $routePlans = $this->axlClient->ListRoutePlanByType([
            'usage' => 'Translation',
        ])->return->routePlan;
        foreach ($routePlans as $routePlan) {
            if (1 == preg_match('~^5.{7}$~', $routePlan->directoryNumber)) {
                $translationPattern = $this->axlClient->GetTransPattern(['uuid' => $routePlan->uuid])->return->pattern;
                $translationPatterns->add($translationPattern);
            }
        }
        var_dump($translationPatterns);



        echo 'OKK';
        return true;
    }
}
