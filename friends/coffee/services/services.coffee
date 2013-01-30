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

angular.module('Friends').factory 'FriendsRequest',
['$http', '$rootScope', 'Config', '_FriendsRequest', 'Publisher',
'FRModel',
($http, $rootScope, Config, _FriendsRequest, Publisher,
FRModel) ->

	Publisher.subscribeModelTo(FRModel, 'friendrequests')
	return new _FriendsRequest($http, $rootScope, Config, Publisher)
]

angular.module('Friends').factory 'FRModel',
['_FRModel', 'Publisher',
(_FRModel, Publisher) ->

	model = new _FRModel()
	return model
]
