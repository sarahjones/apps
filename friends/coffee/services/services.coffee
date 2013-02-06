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

#FriendshipRequest

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




#Friendship

angular.module('Friends').factory 'FriendshipModel',
['_FriendshipModel', 'Publisher',
(_FriendshipModel, Publisher) ->

	model = new _FriendshipModel()
	return model
]

angular.module('Friends').factory 'FriendsRequest',
['$http', '$rootScope', 'Config', '_FriendsRequest', 'Publisher',
'FriendshipModel',
($http, $rootScope, Config, _FriendsRequest, Publisher,
FriendshipModel) ->

	Publisher.subscribeModelTo(FriendshipModel, 'friendships')
	return new _FriendsRequest($http, $rootScope, Config, Publisher)
]


#Facebook
angular.module('Friends').factory 'facebook', [() -> 
    return FB;
]


#This keeps the global namespace cleaner
class FriendsFacebookApp
	constructor: ->
	checkLoginStatus: () ->
		console.log("checkedLoginStatus")
		

window.friendsFacebookApp = new FriendsFacebookApp()

window.fbAsyncInit = ->
	FB.init
		appId: '390927154336244', # App ID
		channelUrl: '//triumph-server.cs.ucsb.edu/~sjones/dev/apps/friends/channel.html', # Channel File
		status: true, # check login status
		cookie: true, # enable cookies to allow the server to access the session
		xfbml: true  # parse XFBML

	friendsFacebookApp.checkLoginStatus()
    	


  

# Load the SDK Asynchronously: https://developers.facebook.com/docs/howtos/login/getting-started/
((d) ->
	id = 'facebook-jssdk'
	ref = d.getElementsByTagName('script')[0]
	return if d.getElementById(id) 
	js = d.createElement('script')
	js.id = id
	js.async = true
	js.src = "//connect.facebook.net/en_US/all.js"
	ref.parentNode.insertBefore(js, ref)
	(document))

