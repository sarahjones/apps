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


# This is an example of a controller. We pass in the Config via Dependency
# Injection. A factory creates a shared instance. You can also share objects
# across controllers this way
angular.module('Friends').factory '_FRController', ->

	class FRController

		constructor: (@$scope, @config, @request, @friendshipRequestModel) ->

			# bind methods on the scope so that you can access them in the
			# controllers child HTML
			@$scope.saveName = (name) =>
				@saveName(name)

			@$scope.acceptFriendshipRequest = (requestor) =>
				@acceptFriendshipRequest(requestor)

		#ajax queries

		saveName: (name) ->
			@request.saveName(@config.routes.saveNameRoute, name)

		acceptFriendshipRequest: (requestor) ->
			@request.acceptFriendshipRequest(requestor)


	return FRController
