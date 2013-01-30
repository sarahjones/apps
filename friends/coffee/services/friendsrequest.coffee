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

# Define your local request functions in an object that inherits from the
# Request object
angular.module('Friends').factory '_FriendsRequest',
['_Request',
(_Request) ->

	class FriendsRequest extends _Request


		constructor: ($http, $rootScope, Config, Publisher) ->
			super($http, $rootScope, Config, Publisher)


		saveName: (route, name) ->
			data =
				somesetting: name

			@post(route, {}, data)


		# Create your local request methods in here
		#
		# myReqest: (route, ...) ->


		#accept a friend request
		acceptFriendshipRequest: (route, friendUid) ->
			data =
				acceptedfriend: friendUid

			@post(route, {}, data)

		#create a friend request
		createFriendshipRequest: (route, recipientUid) ->
			data =
				recipient: recipientUid
			
			@post(route, {}, data)


	return FriendsRequest
]
