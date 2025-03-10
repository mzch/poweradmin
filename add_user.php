<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2010  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2022  Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Script that handles requests to add new users
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2022  Poweradmin Development Team
 * @license     https://opensource.org/licenses/GPL-3.0 GPL
 */

use Poweradmin\BaseController;
use Poweradmin\Permission;

require_once 'inc/toolkit.inc.php';
require_once 'inc/messages.inc.php';

class AddUserController extends BaseController {

    public function run(): void
    {
        $this->checkPermission('user_add_new', _("You do not have the permission to add a new user."));

        if ($this->isPost()) {
            $this->addUser();
        } else {
            $this->showForm();
        }
    }

    private function addUser()
    {
        $v = new Valitron\Validator($_POST);
        $v->rules([
            'required' => ['username', 'email'],
        ]);

        if ($v->validate()) {
            if (do_hook('add_new_user', $_POST)) {
                $this->setMessage('users', 'success', _('The user has been created successfully.'));
                $this->redirect('users.php');
            } else {
                $this->showForm();
            }
        } else {
            $this->showFirstError($v->errors());
        }
    }

    private function showForm()
    {
        $user_edit_templ_perm = do_hook('verify_permission', 'user_edit_templ_perm');
        $user_templates = do_hook('list_permission_templates');

        $username = $_POST['username'] ?? "";
        $fullname = $_POST['fullname'] ?? "";
        $email = $_POST['email'] ?? "";
        $perm_templ = $_POST['perm_templ'] ?? "1";
        $description = $_POST['descr'] ?? "";

        $active_checked = "checked";
        if (isset($_POST['active'])) {
            $active_checked = $_POST['active'] === "1" ? "checked" : "";
        }

        $use_ldap_checked = "";
        if (isset($_POST['use_ldap'])) {
            $use_ldap_checked = $_POST['use_ldap'] === "1" ? "checked" : "";
        }

        $this->render('add_user.html', [
            'username' => $username,
            'fullname' => $fullname,
            'email' => $email,
            'perm_templ' => $perm_templ,
            'description' => $description,
            'active_checked' => $active_checked,
            'use_ldap_checked' => $use_ldap_checked,
            'user_edit_templ_perm' => $user_edit_templ_perm,
            'user_templates' => $user_templates,
            'ldap_use' => $this->config('ldap_use'),
        ]);
    }

    private function showUsers()
    {
        $this->render('users.html', [
            'permissions' => Permission::getPermissions(
                'user_view_others',
                'user_edit_own',
                'user_edit_others',
                'templ_perm_edit',
                'user_is_ueberuser',
            ),
            'perm_templates' => do_hook('list_permission_templates'),
            'users' => do_hook('get_user_detail_list', ""),
            'ldap_use' => $this->config('ldap_use'),
            'session_userid' => $_SESSION["userid"]
        ]);
    }
}

$controller = new AddUserController();
$controller->run();
