HOW TO INSTALL:

http://www.olejon.net/code/spotcommander/?install

HOW TO UPGRADE:

http://www.olejon.net/code/spotcommander/?upgrade

BEFORE UPGRADING:

* Close SpotCommander completely on all your devices before upgrading
* You must reauthorize with Spotify for this version to work (from Profile in the side menu)
* It is recommended that Spotify Premium users update to the latest Spotify desktop client
* Before updating to the latest Spotify desktop client, you should delete the directories ~/.config/spotify and ~/.cache/spotify

CHANGELOG v 13.3:

* Fixes for the previous version's new features (see below)
* Toggle if a Playlist is Collaborative when creating or editing a Playlist
* Keyboard shortcuts to seek back or forward (V and B)
* Many new features for Spotify Premium users, but it is not a requirement (see below)
* Spotify Premium: Use the new Spotify Player API, which still is in beta, but seems stable
* Spotify Premium: Full support for the latest Spotify desktop client (works with some quirks without Premium)
* Spotify Premium: Transfer playback to other devices with Spotify open and active, like phones and tablets (Devices in the side menu)
* Spotify Premium: Full playback control on all devices as long as the Spotify desktop client is running on the computer running SpotCommander, as the commands goes through the new Spotify Player API
* Spotify Premium: Seek 30 seconds back/foward in tracks
* Spotify Premium: Full control over toggling Shuffle and Repeat
* Spotify Premium: Playing a track in any list of tracks, ranging from Library to playlists, artists, albums, recently played etc will continue playing tracks in that list
* Show Recently Played from the Spotify API, so it shows tracks played not using SpotCommander as well
* Show more Top Tracks in Profile
* Fixes for Browse so it uses the official Spotify API
* Removed some noncrucial features to reduce complexity of supporting the previous and the latest Spotify desktop client and Spotify Premium and Free users
* Some can notice various behavior changes because of all this, like the last track in Queue will repeat over and over again, as it acts like a playlist which then only has one track (API behavior)
* Removed Internet Explorer integration (complexity)
* Many small changes and bug fixes (please report an issue or contact me if you find something that does not work as it should)

LICENSE:

Copyright 2017 Ole Jon Bjørkum

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses/.

SPOTIFY DISCLAIMER:

This product uses a Spotify API but is not endorsed, certified or otherwise approved
in any way by Spotify. Spotify is the registered trade mark of the Spotify Group.