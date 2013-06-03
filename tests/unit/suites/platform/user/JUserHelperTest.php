<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  User
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Test class for JUserHelper.
 * Generated by PHPUnit on 2009-10-26 at 22:44:33.
 *
 * @package     Joomla.UnitTest
 * @subpackage  User
 * @since       12.1
*/
class JUserHelperTest extends TestCaseDatabase
{
	/**
	 * @var JUserHelper
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->saveFactoryState();
	}

	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_CsvDataSet
	 *
	 * @since   12.2
	 */
	protected function getDataSet()
	{
		$dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet(',', "'", '\\');

		$dataSet->addTable('jos_users', JPATH_TEST_DATABASE . '/jos_users.csv');
		$dataSet->addTable('jos_user_usergroup_map', JPATH_TEST_DATABASE . '/jos_user_usergroup_map.csv');
		$dataSet->addTable('jos_usergroups', JPATH_TEST_DATABASE . '/jos_usergroups.csv');

		return $dataSet;
	}

	/**
	 * Test cases for userGroups
	 *
	 * Each test case provides
	 * - integer  userid  a user id
	 * - array    group   user group, given as hash
	 *                    group_id => group_name,
	 *                    empty if undefined
	 * - array    error   error info, given as hash
	 *                    with indices 'code', 'msg', and
	 *                    'info', empty, if no error occured
	 *
	 * @see ... (link to where the group and error structures are
	 *      defined)
	 * @return array
	 */
	public function casesGetUserGroups()
	{
		return array(
			'unknownUser' => array(
				1000,
				array(),
				array(
					'code' => 'SOME_ERROR_CODE',
					'msg' => 'JLIB_USER_ERROR_UNABLE_TO_LOAD_USER',
					'info' => ''),
			),
			'publisher' => array(
				43,
				array(5 => 5),
				array(),
			),
			'manager' => array(
				44,
				array(6 => 6),
				array(),
			),
		);
	}

	/**
	 * TestingGetUserGroups().
	 *
	 * @param   integer  $userid    User ID
	 * @param   mixed    $expected  User object or empty array if unknown
	 * @param   array    $error     Expected error info
	 *
	 * @dataProvider casesGetUserGroups
	 * @covers  JUserHelper::getUserGroups
	 * @return  void
	 */
	public function testGetUserGroups($userid, $expected, $error)
	{
		$this->assertThat(
			JUserHelper::getUserGroups($userid),
			$this->equalTo($expected)
		);
	}

	/**
	 * Test cases for userId
	 *
	 * @return array
	 */
	public function casesGetUserId()
	{
		return array(
			'admin' => array(
				'admin',
				42,
				array(),
			),
			'unknown' => array(
				'unknown',
				null,
				array(),
			),
		);
	}

	/**
	 * TestingGetUserId().
	 *
	 * @param   string   $username  User name
	 * @param   integer  $expected  Expected user id
	 * @param   array    $error     Expected error info
	 *
	 * @dataProvider casesGetUserId
	 * @covers  JUserHelper::getUserId
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	public function testGetUserId($username, $expected, $error)
	{
		$expResult = $expected;
		$this->assertThat(
			JUserHelper::getUserId($username),
			$this->equalTo($expResult)
		);

	}

	/**
	 * Test cases for testAddUserToGroup
	 *
	 * @return array
	 */
	public function casesAddUserToGroup()
	{
		return array(
			'publisher' => array(
				43,
				6,
				true
			),
			'manager' => array(
				44,
				6,
				true
			),
		);
	}
	/**
	 * Testing addUserToGroup().
	 *
	 * @param   string   $userId    User id
	 * @param   integer  $groupId   Group to add user to
	 * @param   boolean  $expected  Expected params
	 *
	 * @dataProvider casesAddUsertoGroup
	 * @covers  JUserHelper::addUsertoGroup
	 * @return  void
	 *
	 * @since   12.3
	 */
	public function testAddUserToGroup($userId, $groupId, $expected)
	{
		$this->assertThat(
			JUserHelper::addUserToGroup($userId, $groupId),
			$this->equalTo($expected)
		);
	}

	/**
	 * Testing addUserToGroup() with expected exception.
	 *
	 * @return  void
	 *
	 * @since   12.3
	 * @expectedException  RuntimeException
	 * @covers  JUserHelper::addUsertoGroup
	 */
	public function testAddUserToGroupException()
	{
		JUserHelper::addUserToGroup(44, 99);
	}

	/**
	 * Test cases for testRemoveUserFromGroup
	 *
	 * @return array
	 */
	public function casesRemoveUserFromGroup()
	{
		return array(
			'publisher' => array(
				43,
				8,
				true
			),
			'manager' => array(
				44,
				6,
				true
			),
		);
	}

	/**
	 * Testing removeUserFromGroup().
	 *
	 * @param   string   $userId    User id
	 * @param   integer  $groupId   Group to remove user from
	 * @param   boolean  $expected  Expected params
	 *
	 * @dataProvider casesRemoveUserFromGroup
	 * @covers  JUserHelper::removeUserFromGroup
	 * @return  void
	 */
	public function testRemoveUserFromGroup($userId, $groupId, $expected)
	{
		$this->markTestSkipped('Unexpected test failure in CMS environment');
		$this->assertThat(
			JUserHelper::removeUserFromGroup($userId, $groupId),
			$this->equalTo($expected)
		);
	}

	/**
	 * Test cases for testActivateUser
	 *
	 * @return array
	 */
	public function casesActivateUser()
	{
		return array(
			'Valid User' => array(
				'30cc6de70fb18231196a28dd83363d57',
				true),
			'Invalid User' => array(
				'30cc6de70fb18231196a28dd83363d72',
				false),
		);
	}
	/**
	 * Testing activateUser().
	 *
	 * @param   string   $activation  Activation string
	 * @param   boolean  $expected    Expected params
	 *
	 * @dataProvider casesActivateUser
	 * @covers  JUserHelper::activateUser
	 * @return  void
	 *
	 * @since   12.3
	 */
	public function testActivateUser($activation, $expected)
	{
		$this->markTestSkipped('Unexpected test failure in CMS environment');
		$this->assertThat(
			JUserHelper::activateUser($activation),
			$this->equalTo($expected)
		);
	}
}
