<div id="app"
	ng-app="Friends"
	ng-controller="FriendshipController"
	ng-init="name='{{somesetting}}'">

	<h1 class="heading">Friends</h1>

	<!--<p>The URL Parameter for the index page is: {{test}}</p>

	<p ng-show="name">Welcome home [[name | leetIt]]!</p>

	<form class="centered">
	        My name is <input type="text" placeholder="anonymous" ng-model="name">
	        <button ng-click="saveName(name)">Remember my name</button>
	</form>
	-->

	<p>Your friends are: </p>
	{% for friend in friends %}
		* {{ friend }}
	{% else %}
		You don't have any friends.
	{% endfor %}

</div>



