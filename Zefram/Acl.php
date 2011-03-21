<?php

class Zefram_Acl extends Zend_Acl
{
    const CURRENT_ROLE = null;

    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        if (null === $role) {
            $role = $this->getCurrentRole();
        }

        return parent::isAllowed($role, $resource, $privilege);
    }

    /**
     * Determine whether current user belongs to a given role,
     * or current role inherits from the given role.
     *
     * @param string $role
     * @return bool
     */
    public function is($role)
    {
        $currentRole = $this->getCurrentRole();
        $is = $currentRole == $role;
        if (!$is) {
            try {
                $is = $this->inheritsRole($currentRole, $role, false);
            } catch (Zend_Acl_Role_Registry_Exception $e) {
                $is = false;
            }
        }
        return $is;
    }

    /**
     * Returns role of the currently authenticated user, or
     * 'guest' if no authenticated user is present.
     *
     * @return string
     */
    public function getCurrentRole()
    {
        $auth = Zend_Auth::getInstance();
        $role = null;

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            if (is_array($identity) && isset($identity['role'])) {
                $role = $identity['role'];
            } elseif (is_object($identity) && isset($identity->role)) {
                $role = $identity->role;
            }
        }
        if (!$role) {
            $role = 'guest';
        }

        return $role;
    }
}
