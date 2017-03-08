package net.olejon.spotcommander;

/*

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

*/

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.v7.app.AppCompatActivity;

public class ShareActivity extends AppCompatActivity
{
    private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	// Create activity
	@Override
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

		// Settings
		PreferenceManager.setDefaultValues(mContext, R.xml.settings, false);

		// Intent
		final Intent intent = getIntent();

        String shareUri = null;

		if(intent.getAction() != null && intent.getAction().equals(Intent.ACTION_SEND))
		{
            shareUri = intent.getStringExtra(Intent.EXTRA_TEXT);
		}
		else if(intent.getAction() != null && intent.getAction().equals(Intent.ACTION_VIEW))
		{
            shareUri = intent.getData().toString();
		}

		// Share uri
		if(shareUri == null)
        {
            finish();
        }
        else
        {
            mTools.setSharedPreferencesString("SHARE_URI", shareUri);

			final Intent launchActivityIntent = new Intent(mContext, WebViewActivity.class);
			launchActivityIntent.addFlags(Intent.FLAG_ACTIVITY_NO_ANIMATION);
			startActivity(launchActivityIntent);
		}
	}
}