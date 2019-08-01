<?php
namespace App\Components\Phones\Cisco;

class CiscoPhone8945 extends CiscoPhone
{
    /**
     * @return array
     * @throws \Exception
     */
    public function realtimeData(): array
    {
        return array_merge($this->xmlBasicInfo(), $this->xmlNetInfo(), $this->htmlPortInfo());
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function htmlPortInfo()
    {
        $content = $this->urlContent('http://' . $this->ip . '/PortInformation?1');
        if (false === $content) {
            throw new \Exception('PhonePortInfo is not available');
        }
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);
        $fieldsMap = [
            'neighbordeviceid' => 'cdpNeighborDeviceId',
            'neighboripaddress' => 'cdpNeighborIP',
            'neighborport' => 'cdpNeighborPort',
        ];
        $portInfo = [
            'cdpNeighborDeviceId' => '',
            'cdpNeighborIP' => '',
            'cdpNeighborPort' => '',
        ];
        $skipOne = false;
        $foundValue = false;
        $foundField = '';
        foreach (explode("\n", $document->textContent) as $item) {
            if ($skipOne) {
                $foundValue = true;
                $skipOne = false;
                continue;
            }
            if ($foundValue) {
                $portInfo[$foundField] = $item;
                $foundValue = false;
                continue;
            }
            $item = mb_strtolower(mb_ereg_replace('[ |\\n|\\r]+', '', $item));
            if (!is_null($fieldsMap[$item])) {
                $foundField = $fieldsMap[$item];
                $skipOne = true;
            }
        }
        return $portInfo;
    }
}
