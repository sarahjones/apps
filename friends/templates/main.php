<div id="app" ng-app="Friends"
	ng-init="name='{{somesetting}}'">

	<h1 class="heading">Friends</h1>

	<!--<p>The URL Parameter for the index page is: {{test}}</p>-->

	<p ng-show="name">Welcome home [[name | leetIt]]!</p>

	
<!--	<form class="centered">
	        My name is <input type="text" placeholder="anonymous" ng-model="name">
	        <button ng-click="saveName(name)">Remember my name</button>
	</form>
-->	

	<div ng-controller="FRController">
		<form class="centered">
			<input type="text" placeholder="Enter username" ng-model="recipient">
			<button ng-click="createFriendshipRequest(recipient)">Request Friendship</button>
		</form>
	</div>

	<p>Your friends are: </p>
	{% for friend in friends %}
		* {{ friend }}
	{% else %}
		You don't have any friends.
	{% endfor %}

</div>



