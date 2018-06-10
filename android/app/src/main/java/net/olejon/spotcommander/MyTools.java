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

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.Configuration;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.net.wifi.WifiManager;
import android.preference.PreferenceManager;
import android.support.customtabs.CustomTabsIntent;
import android.support.v4.app.NavUtils;
import android.support.v4.app.TaskStackBuilder;
import android.support.v4.content.ContextCompat;
import android.util.Base64;
import android.util.Log;
import android.widget.Toast;

import com.android.volley.DefaultRetryPolicy;
import com.android.volley.NetworkResponse;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.BasicNetwork;
import com.android.volley.toolbox.DiskBasedCache;
import com.android.volley.toolbox.HurlStack;
import com.android.volley.toolbox.StringRequest;

import java.util.HashMap;

class MyTools
{
	private final Context mContext;

	MyTools(Context context)
	{
		mContext = context;
	}

	// Default shared preferences
	boolean getDefaultSharedPreferencesBoolean(String preference)
	{
		SharedPreferences sharedPreferences = PreferenceManager.getDefaultSharedPreferences(mContext);
		return sharedPreferences.getBoolean(preference, false);
	}

	// Shared preferences
	String getSharedPreferencesString(String preference)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		return sharedPreferences.getString(preference, "");
	}

	void setSharedPreferencesString(String preference, String string)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

		sharedPreferencesEditor.putString(preference, string);
		sharedPreferencesEditor.apply();
	}

	boolean getSharedPreferencesBoolean(String preference)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		return sharedPreferences.getBoolean(preference, false);
	}

	void setSharedPreferencesBoolean(String preference, boolean bool)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

		sharedPreferencesEditor.putBoolean(preference, bool);
		sharedPreferencesEditor.apply();
	}

	long getSharedPreferencesLong(String preference)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		return sharedPreferences.getLong(preference, 0);
	}

	void setSharedPreferencesLong(String preference, long l)
	{
		SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
		SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

		sharedPreferencesEditor.putLong(preference, l);
		sharedPreferencesEditor.apply();
	}

	// Project version
	String getProjectVersionName()
	{
		try
		{
			return mContext.getPackageManager().getPackageInfo(mContext.getPackageName(), 0).versionName;
		}
		catch(Exception e)
		{
			Log.e("MyTools", Log.getStackTraceString(e));
		}

		return "0.0";
	}

	// Network
	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	boolean isDeviceConnected()
	{
		ConnectivityManager connectivityManager = (ConnectivityManager) mContext.getSystemService(Context.CONNECTIVITY_SERVICE);

		if(connectivityManager == null) return false;

		NetworkInfo networkInfo = connectivityManager.getActiveNetworkInfo();

		return (networkInfo.isConnected());
	}

	String getCurrentNetwork()
	{
		WifiManager wifiManager = (WifiManager) mContext.getApplicationContext().getSystemService(Context.WIFI_SERVICE);

		if(wifiManager != null && wifiManager.isWifiEnabled()) return wifiManager.getConnectionInfo().getSSID();

		return "";
	}

	// Computer
	String[] getComputer(long id)
	{
		SQLiteDatabase mDatabase = new MainSQLiteHelper(mContext).getReadableDatabase();

		String[] queryColumns = {MainSQLiteHelper.COLUMN_URI, MainSQLiteHelper.COLUMN_USERNAME, MainSQLiteHelper.COLUMN_PASSWORD};
		Cursor mCursor = mDatabase.query(MainSQLiteHelper.TABLE_COMPUTERS, queryColumns, MainSQLiteHelper.COLUMN_ID+" = "+id, null, null, null, null);

		String uri = "";
		String username = "";
		String password = "";

		if(mCursor.moveToFirst())
		{
			uri = mCursor.getString(mCursor.getColumnIndexOrThrow(MainSQLiteHelper.COLUMN_URI));
			username = mCursor.getString(mCursor.getColumnIndexOrThrow(MainSQLiteHelper.COLUMN_USERNAME));
			password = mCursor.getString(mCursor.getColumnIndexOrThrow(MainSQLiteHelper.COLUMN_PASSWORD));
		}

		mCursor.close();
		mDatabase.close();

		return new String[] {uri, username, password};
	}

	// Allow landscape?
	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	boolean allowLandscape()
	{
		int size = mContext.getResources().getConfiguration().screenLayout & Configuration.SCREENLAYOUT_SIZE_MASK;

		return ((size) == Configuration.SCREENLAYOUT_SIZE_LARGE || (size) == Configuration.SCREENLAYOUT_SIZE_XLARGE);
	}

	// Up navigation
	void navigateUp(Activity activity)
	{
		Intent navigateUpIntent = NavUtils.getParentActivityIntent(activity);

		if(navigateUpIntent != null && NavUtils.shouldUpRecreateTask(activity, navigateUpIntent) || navigateUpIntent != null && activity.isTaskRoot())
		{
			TaskStackBuilder.create(mContext).addNextIntentWithParentStack(navigateUpIntent).startActivities();
		}
		else
		{
			NavUtils.navigateUpFromSameTask(activity);
		}
	}

	// Toast
	void showToast(String toast, int length)
	{
		Toast.makeText(mContext, toast, length).show();
	}

	// Open URI
	void openChromeCustomTabsUri(String uri)
	{
		String packageName = "com.android.chrome";

		boolean isGoogleChromeInstalled = false;

		try
		{
			mContext.getPackageManager().getApplicationInfo(packageName, 0);

			isGoogleChromeInstalled = true;
		}
		catch(Exception e)
		{
			Log.e("MyTools", Log.getStackTraceString(e));
		}

		if(isGoogleChromeInstalled)
		{
			CustomTabsIntent.Builder builder = new CustomTabsIntent.Builder();
			builder.setToolbarColor(ContextCompat.getColor(mContext, R.color.dark_green));

			CustomTabsIntent customTabsIntent = builder.build();
			customTabsIntent.intent.setPackage(packageName);
			customTabsIntent.launchUrl(mContext, Uri.parse(uri));
		}
		else
		{
			Intent browserIntent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
			mContext.startActivity(browserIntent);
		}
	}

	// Remote control
	void remoteControl(long id, final String action, final String data)
	{
		String[] computer = getComputer(id);

		String uri = computer[0];

		final String username = computer[1];
		final String password = computer[2];

		if(uri.equals(""))
		{
			showToast(mContext.getString(R.string.remote_control_computer_not_found), 1);
		}
		else
		{
			final String remoteControlError = mContext.getString(R.string.remote_control_error);
			final String remoteControlAuthenticationFailed = mContext.getString(R.string.remote_control_authentication_failed);

			final RequestQueue requestQueue = new RequestQueue(new DiskBasedCache(mContext.getCacheDir(), 0), new BasicNetwork(new HurlStack()));

			requestQueue.start();

			StringRequest stringRequest = new StringRequest(Request.Method.POST, uri+"/main.php", new Response.Listener<String>()
			{
				@Override
				public void onResponse(String response)
				{
					requestQueue.stop();
				}
			}, new Response.ErrorListener()
			{
				@Override
				public void onErrorResponse(VolleyError error)
				{
					requestQueue.stop();

					NetworkResponse networkResponse = error.networkResponse;

					if(networkResponse != null)
					{
						int statusCode = networkResponse.statusCode;

						if(statusCode == 401)
						{
							showToast(remoteControlAuthenticationFailed, 1);
						}
						else
						{
							showToast(remoteControlError+statusCode, 1);
						}
					}
				}
			})
			{
				@Override
				public HashMap<String,String> getHeaders()
				{
					HashMap<String,String> hashMap = new HashMap<>();

					if(!username.equals("") && !password.equals("")) hashMap.put("Authorization", "Basic "+Base64.encodeToString((username+":"+password).getBytes(), Base64.NO_WRAP));

					return hashMap;
				}

				@Override
				protected HashMap<String,String> getParams()
				{
					HashMap<String,String> hashMap = new HashMap<>();

					hashMap.put("action", action);
					hashMap.put("data", data);

					return hashMap;
				}
			};

			stringRequest.setRetryPolicy(new DefaultRetryPolicy(2500, 0, 0));

			requestQueue.add(stringRequest);
		}
	}
}