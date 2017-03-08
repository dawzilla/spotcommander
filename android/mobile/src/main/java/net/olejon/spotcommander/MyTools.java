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
import android.net.wifi.WifiManager;
import android.os.AsyncTask;
import android.preference.PreferenceManager;
import android.support.v4.app.NavUtils;
import android.support.v4.app.TaskStackBuilder;
import android.util.Base64;
import android.util.Log;
import android.widget.Toast;

import com.android.volley.Cache;
import com.android.volley.DefaultRetryPolicy;
import com.android.volley.Network;
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

final class MyTools
{
	private final Context mContext;

	public MyTools(Context context)
	{
		mContext = context;
	}

    // Default shared preferences
    public boolean getDefaultSharedPreferencesBoolean(final String preference)
    {
    	final SharedPreferences sharedPreferences = PreferenceManager.getDefaultSharedPreferences(mContext);
    	return sharedPreferences.getBoolean(preference, false);
    }

    // Shared preferences
    public String getSharedPreferencesString(final String preference)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	return sharedPreferences.getString(preference, "");
    }

    public void setSharedPreferencesString(final String preference, final String string)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	final SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

    	sharedPreferencesEditor.putString(preference, string);
    	sharedPreferencesEditor.apply();
    }

    public boolean getSharedPreferencesBoolean(final String preference)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	return sharedPreferences.getBoolean(preference, false);
    }

    public void setSharedPreferencesBoolean(final String preference, final boolean bool)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	final SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

    	sharedPreferencesEditor.putBoolean(preference, bool);
    	sharedPreferencesEditor.apply();
    }

    public long getSharedPreferencesLong(final String preference)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	return sharedPreferences.getLong(preference, 0);
    }

    public void setSharedPreferencesLong(final String preference, final long l)
    {
    	final SharedPreferences sharedPreferences = mContext.getSharedPreferences("PREFERENCES", 0);
    	final SharedPreferences.Editor sharedPreferencesEditor = sharedPreferences.edit();

    	sharedPreferencesEditor.putLong(preference, l);
    	sharedPreferencesEditor.apply();
    }

    // Project version
    public String getProjectVersionName()
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

    // Current network
    public String getCurrentNetwork()
    {
    	final WifiManager wifiManager = (WifiManager) mContext.getApplicationContext().getSystemService(Context.WIFI_SERVICE);

    	if(wifiManager.isWifiEnabled()) return wifiManager.getConnectionInfo().getSSID();

    	return "";
    }

    // Computer
    public String[] getComputer(final long id)
    {
    	final SQLiteDatabase mDatabase = new MainSQLiteHelper(mContext).getReadableDatabase();

		final String[] queryColumns = {MainSQLiteHelper.COLUMN_URI, MainSQLiteHelper.COLUMN_USERNAME, MainSQLiteHelper.COLUMN_PASSWORD};
		final Cursor mCursor = mDatabase.query(MainSQLiteHelper.TABLE_COMPUTERS, queryColumns, MainSQLiteHelper.COLUMN_ID+" = "+id, null, null, null, null);

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
    public boolean allowLandscape()
    {
        final int size = mContext.getResources().getConfiguration().screenLayout & Configuration.SCREENLAYOUT_SIZE_MASK;

        return ((size) == Configuration.SCREENLAYOUT_SIZE_LARGE || (size) == Configuration.SCREENLAYOUT_SIZE_XLARGE);
    }

    // Up navigation
    public void navigateUp(final Activity activity)
    {
        final Intent navigateUpIntent = NavUtils.getParentActivityIntent(activity);

        if(NavUtils.shouldUpRecreateTask(activity, navigateUpIntent) || activity.isTaskRoot())
        {
            TaskStackBuilder.create(mContext).addNextIntentWithParentStack(navigateUpIntent).startActivities();
        }
        else
        {
            NavUtils.navigateUpFromSameTask(activity);
        }
    }

    // Toast
    public void showToast(final String toast, final int length)
    {
        Toast.makeText(mContext, toast, length).show();
    }

    // Remote control
    public void remoteControl(final long id, final String action, final String data)
    {
    	final String[] computer = getComputer(id);

    	if(computer[0].equals(""))
    	{
    		showToast(mContext.getString(R.string.remote_control_computer_not_found), 1);
    	}
    	else
    	{
    		final RemoteControlTask remoteControlTask = new RemoteControlTask();
    		remoteControlTask.execute(computer[0], computer[1], computer[2], action, data);
    	}
    }

    private class RemoteControlTask extends AsyncTask<String, Void, Void>
    {
	    @Override
	    protected Void doInBackground(String... strings)
	    {
            final Cache cache = new DiskBasedCache(mContext.getCacheDir(), 0);

            final Network network = new BasicNetwork(new HurlStack());

            final RequestQueue requestQueue = new RequestQueue(cache, network);

            requestQueue.start();

            final String uri = strings[0];
            final String username = strings[1];
            final String password = strings[2];
            final String action = strings[3];
            final String data = strings[4];

            final StringRequest stringRequest = new StringRequest(Request.Method.POST, uri+"/", new Response.Listener<String>()
            {
                @Override
                public void onResponse(String response)
                {
                    requestQueue.stop();
                }
            },new Response.ErrorListener()
            {
                @Override
                public void onErrorResponse(VolleyError error)
                {
                    requestQueue.stop();

                    final NetworkResponse networkResponse = error.networkResponse;

                    if(networkResponse != null)
                    {
                        final int statusCode = networkResponse.statusCode;

                        if(statusCode == 401)
                        {
                            showToast(mContext.getString(R.string.remote_control_authentication_failed), 1);
                        }
                        else
                        {
                            showToast(mContext.getString(R.string.remote_control_error)+statusCode, 1);
                        }
                    }
                }
            })
            {
                @Override
                protected HashMap<String, String> getParams()
                {
                    final HashMap<String, String> hashMap = new HashMap<>();

                    hashMap.put("action", action);
                    hashMap.put("data", data);

                    return hashMap;
                }

                @Override
                public HashMap<String, String> getHeaders()
                {
                    final HashMap<String, String> hashMap = new HashMap<>();

                    if(!username.equals("") && !password.equals("")) hashMap.put("Authorization", "Basic "+Base64.encodeToString((username+":"+password).getBytes(), Base64.NO_WRAP));

                    return hashMap;
                }
            };

            stringRequest.setRetryPolicy(new DefaultRetryPolicy(2500, 0, 0));

            requestQueue.add(stringRequest);

            return null;
	    }
    }
}