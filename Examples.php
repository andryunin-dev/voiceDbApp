<?php

public function actionDelRegion($id)
{
    try {
        Region::getDbConnection()->beginTransaction();


        Region::getDbConnection()->commitTransaction();
    } catch (MultiException $e) {
        Region::getDbConnection()->rollbackTransaction();
        $this->data->errors = $e;
    } catch (Exception $e) {
        Region::getDbConnection()->rollbackTransaction();
        $this->data->errors = (new MultiException())->add($e);
    }
}

