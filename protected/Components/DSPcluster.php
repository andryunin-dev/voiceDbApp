<?php
namespace App\Components;

use App\Models\Cluster;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class DSPcluster extends Std
{
    /**
     * @param Std $data
     * @return bool
     * @throws MultiException
     */
    public function process(Std $data)
    {
        $cluster = Cluster::findByColumn('title', $data->hostname);
        if (false === $cluster) {
            $cluster = new Cluster();
            $cluster->fill([
                'title' => $data->hostname,
            ]);
            $cluster->save();
        }

        // IP address прописываем только у первого Appliance, входящего в состав кластера
        $n = 1;
        $errors = new MultiException();
        foreach ($data->clusterAppliances as $applianceData) {
            try {
                if (1 != $n) {
                    $applianceData->ip = null;
                } else {
                    if (empty($applianceData->ip)) {
                        throw new Exception('CLUSTER UPDATE: [message]=Fist appliance does not have the management ip; [cluster]=' . $cluster->title . '; [errorData]=' . json_encode($applianceData) . '; [data]=' . json_encode($data));
                    }
                    $n++;
                }
                $applianceData->cluster = $cluster;
                (new DSPappliance())->process($applianceData);
            } catch (\Throwable $e) {
                $errors->addException($e->getMessage());
            }
        }
        if (!$errors->isEmpty()) {
            throw $errors;
        }
        return true;
    }
}
