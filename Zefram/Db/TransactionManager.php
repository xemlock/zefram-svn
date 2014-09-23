<?php

interface Zefram_Db_TransactionManager
{
    /**
     * @return bool
     */
    public function inTransaction();

    /**
     * @return mixed
     */
    public function beginTransaction();

    /**
     * @return mixed
     */
    public function commit();

    /**
     * @return mixed
     */
    public function rollBack();
}
