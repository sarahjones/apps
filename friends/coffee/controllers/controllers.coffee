###
# ownCloud
#
# @author Sarah Jones
# Copyright (c) 2012 - Bernhard Posselt <nukeawhale@gmail.com>
#
# This file is licensed under the Affero General Public License version 3 or later.
# See the COPYING-README file
#
###

angular.module('Friends').controller 'ExampleController',
['$scope', 'Config', 'FriendsRequest', '_ExampleController', 'ItemModel',
($scope, Config, FriendsRequest, _ExampleController, ItemModel) ->
	return new _ExampleController($scope, Config, FriendsRequest, ItemModel)
]


angular.module('Friends').controller 'FRController',
['$scope', 'Config', 'FriendsRequest', '_FRController', 'FriendRequestModel',
($scope, Config, FriendsRequest, _FRController, FriendRequestModel) ->
	return new _FRController($scope, Config, FriendsRequest, FriendRequestModel)
]
