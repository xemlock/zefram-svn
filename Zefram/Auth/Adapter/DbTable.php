<?php

class Zefram_Auth_Adapter_DbTable implements Zend_Auth_Adapter_Interface
{
    protected $_db;

    public function __construct($dbAdapter)
    {
        if (null === $dbAdapter) {
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('No database adapter present');
        }
        $this->_db = $dbAdapter;
    }

    const DEFAULT_TABLE_NAME        = 'users';
    const DEFAULT_IDENTITY_COLUMN   = 'username';
    const DEFAULT_CREDENTIAL_COLUMN = 'password';
    const CREDENTIAL_TREATMENT_COLUMN = 'credential_treatment';

    protected $_tableName = self::DEFAULT_TABLE_NAME;
    protected $_identity;
    protected $_identityColumn = self::DEFAULT_IDENTITY_COLUMN;
    protected $_credential;
    protected $_credentialColumn = self::DEFAULT_CREDENTIAL_COLUMN;
    protected $_credentialTreatment = null;
    protected $_credentialCallback = null;
    protected $_resultRow = null;

    public function setTableName($tableName)            { $this->_tableName = $tableName; }
    public function setIdentity($identity)              { $this->_identity = $identity; }
    public function setIdentityColumn($column)          { $this->_identityColumn = $column; }
    public function setCredentialTreatment($treatment)  { $this->_credentialTreatment = $treatment; }
    public function setCredentialCallback($callback)    { $this->_credentialCallback = $callback; }
    public function setCredential($credential)          { $this->_credential = $credential; }
    public function setCredentialColumn($column)        { $this->_credentialColumn = $column; }

    protected $_result;
    protected $_resultInfo = array(
        'code' => Zend_Auth_Result::FAILURE, 
        'identity' => null,
        'messages' => array(),
    );

    protected function _result() 
    {
        return new Zend_Auth_Result(
            $this->_resultInfo['code'],
            $this->_resultInfo['identity'],
            $this->_resultInfo['messages']
        );
    }


    /**
     * May be overriden in subclasses.
     */
    protected function fetchRows()
    {
        $credentialColumn = $this->_credentialColumn;
        $identityColumn = $this->_identityColumn;
        // If credentialCallback is set and credentialTreatment is not,
        // do not use default credential treatment (password = ?)
        $sql = sprintf(
            "SELECT *, (CASE WHEN %s THEN 1 ELSE 0 END) AS %s FROM %s WHERE %s = ?",
            null !== $this->_credentialTreatment 
                    ? $this->_credentialTreatment
                    : (
                        null !== $this->_credentialCallback
                        ? 1
                        : ($this->_db->quoteIdentifier($this->_credentialColumn) . ' = ' . $this->_db->quote($this->_credential))
                      ),
            self::CREDENTIAL_TREATMENT_COLUMN,
            $this->_db->quoteIdentifier($this->_tableName),
            $this->_db->quoteIdentifier($this->_identityColumn)
        );
        echo $sql;
        try {
            $rows = $this->_db->fetchAssoc($sql, $this->_identity);
            return $rows;
        } catch (Exception $e) {
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('The supplied parameters failed to produce a valid sql statement, '
                                                . 'please check table and column names for validity.', 0, $e);
         }
    }

    public function authenticate()
    {
        $rows = $this->fetchRows();
        switch (count($rows)) {
            case 1: 
                break;
            case 0:
                $this->_resultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
                $this->_resultInfo['messages'][] = 'A record with the supplied identity could not be found.';
                return $this->_result();                
            default:
                $this->_resultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
                $this->_resultInfo['messages'][] = 'More than one record matches the supplied identity.';
                return $this->_result();
        }
        $row = reset($rows);
        if ($row[self::CREDENTIAL_TREATMENT_COLUMN] != '1') {
            $this->_resultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $this->_resultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->_result();
        }

        unset($row[self::CREDENTIAL_TREATMENT_COLUMN]);       
        if ($this->_credentialCallback) {
            $validationResult = false;
            require_once 'Zefram/Auth/PasswordMangler.php';
            if ($this->_credentialCallback instanceof Zefram_Auth_PasswordMangler) {
                $validationResult = $this->_credentialCallback->validate($this->_credential, $row[$this->_credentialColumn], $row);
            } else {
                $validationResult = call_user_func($this->_credentialCallback, $this->_credential, $row[$this->_credentialColumn], $row);
            }
            if (!$validationResult) {
                $this->_resultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                $this->_resultInfo['messages'][] = 'Supplied credential is invalid.';
                return $this->_result();
            }
        }
        $this->_resultRow = $row;
        $this->_resultInfo['code'] = Zend_Auth_Result::SUCCESS;
        $this->_resultInfo['messages'][] = 'Authentication successful.';
        return $this->_result(); 
    }

    public function getResultRowObject($returnColumns = null, $omitColumns = null) 
    {
        if (null === $this->_resultRow) return null;
        $row = new stdClass;
        if (null !== $returnColumns) {
            foreach ((array) $returnColumns as $column) {
                if (isset($this->_resultRow[$column])) {
                    $row->{$column} = $this->_resultRow[$column];
                }
            }
        } else if (null !== $omitColumns) {
            $omitColumns = (array) $omitColumns;
            foreach ($this->_resultRow as $name => $value) {
                if (!in_array($name, $omitColumns)) {
                    $row->{$name} = $value;
                }
            }
        } else {
            foreach ($this->_resultRow as $name => $value) {
                $row->{$name} = $value;
            }
        }
        return $row;
    }
}
