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

import android.Manifest;
import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.pm.ActivityInfo;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.annotation.NonNull;
import android.support.design.widget.FloatingActionButton;
import android.support.v4.app.ActivityCompat;
import android.support.v4.widget.SimpleCursorAdapter;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.animation.AnimationUtils;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.AdapterView.OnItemLongClickListener;
import android.widget.Button;
import android.widget.ListView;

import com.afollestad.materialdialogs.DialogAction;
import com.afollestad.materialdialogs.MaterialDialog;
import com.android.volley.Cache;
import com.android.volley.DefaultRetryPolicy;
import com.android.volley.Network;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.BasicNetwork;
import com.android.volley.toolbox.DiskBasedCache;
import com.android.volley.toolbox.HurlStack;
import com.android.volley.toolbox.JsonObjectRequest;
import com.google.android.gms.analytics.GoogleAnalytics;
import com.google.android.gms.analytics.HitBuilders;
import com.google.android.gms.analytics.Tracker;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.common.api.ResultCallback;
import com.google.android.gms.wearable.NodeApi;
import com.google.android.gms.wearable.Wearable;

import org.json.JSONObject;

import java.net.URLEncoder;

public class MainActivity extends AppCompatActivity
{
    private final int PERMISSIONS_REQUEST_READ_PHONE_STATE = 0;

    private final Activity mActivity = this;

    private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

    private GoogleApiClient mGoogleApiClient;

	private SQLiteDatabase mDatabase;
	private Cursor mCursor;

    private FloatingActionButton mFloatingActionButton;
	private ListView mListView;

	// Create activity
	@Override
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

        // Settings
        PreferenceManager.setDefaultValues(mContext, R.xml.settings, false);

        // Allow landscape?
        if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

        // Google API client
        mGoogleApiClient = new GoogleApiClient.Builder(mContext).addApiIfAvailable(Wearable.API).build();

		// Database
		mDatabase = new MainSQLiteHelper(mContext).getWritableDatabase();

		// Layout
		setContentView(R.layout.activity_main);

        // Toolbar
        final Toolbar toolbar = (Toolbar) findViewById(R.id.main_toolbar);
        toolbar.setTitle(getString(R.string.main_title));

        setSupportActionBar(toolbar);

		// Listview
        mListView = (ListView) findViewById(R.id.main_list);

		final View listViewHeader = getLayoutInflater().inflate(R.layout.activity_main_subheader, mListView, false);
        mListView.addHeaderView(listViewHeader, null, false);

        final View listViewEmpty = findViewById(R.id.main_empty);
        mListView.setEmptyView(listViewEmpty);

        mListView.setLongClickable(true);

        mListView.setOnItemClickListener(new OnItemClickListener()
        {
            @Override
            public void onItemClick(AdapterView< ?> parent, View view, int position, long id)
            {
                openComputer(id);
            }
        });

        mListView.setOnItemLongClickListener(new OnItemLongClickListener()
        {
            @Override
            public boolean onItemLongClick(AdapterView<?> adapterView, View view, int i, final long id)
            {
                new MaterialDialog.Builder(mContext).title(R.string.main_remove_computer_dialog_title).content(getString(R.string.main_remove_computer_dialog_message)).positiveText(R.string.main_remove_computer_dialog_positive_button).negativeText(R.string.main_remove_computer_dialog_negative_button).onPositive(new MaterialDialog.SingleButtonCallback()
                {
                    @Override
                    public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
                    {
                        removeComputer(id);

                        listComputers();
                    }
                }).contentColorRes(R.color.black).negativeColorRes(R.color.black).show();

                return true;
            }
        });

        // Floating action button
        mFloatingActionButton = (FloatingActionButton) findViewById(R.id.main_fab);

        mFloatingActionButton.setOnClickListener(new View.OnClickListener()
        {
            @Override
            public void onClick(View view)
            {
                Intent intent = new Intent(mContext, AddComputerActivity.class);
                startActivity(intent);
            }
        });

        // Donate button
        final Button button = (Button) findViewById(R.id.main_make_donation_button);

        button.setOnClickListener(new View.OnClickListener()
        {
            @Override
            public void onClick(View view)
            {
                Intent intent = new Intent(mContext, DonateActivity.class);
                startActivity(intent);
            }
        });

        // Information dialog
        if(Build.VERSION.SDK_INT < Build.VERSION_CODES.KITKAT)
        {
            if(!mTools.getSharedPreferencesBoolean("HIDE_INFORMATION_DIALOG_75"))
            {
                new MaterialDialog.Builder(mContext).title(R.string.main_information_dialog_title).content(getString(R.string.main_information_dialog_message)).positiveText(R.string.main_information_dialog_positive_button).onPositive(new MaterialDialog.SingleButtonCallback()
                {
                    @Override
                    public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
                    {
                        mTools.setSharedPreferencesBoolean("HIDE_INFORMATION_DIALOG_75", true);
                    }
                }).contentColorRes(R.color.black).show();
            }
        }

        // Message dialog
        String version = mTools.getProjectVersionName();
        String device = "";

        try
        {
            device = (Build.MANUFACTURER == null || Build.MODEL == null) ? "" : URLEncoder.encode(Build.MANUFACTURER+" "+Build.MODEL+" "+Build.VERSION.SDK_INT, "utf-8");
        }
        catch(Exception e)
        {
            Log.e("MainActivity", Log.getStackTraceString(e));
        }

        final Cache cache = new DiskBasedCache(getCacheDir(), 0);

        final Network network = new BasicNetwork(new HurlStack());

        final RequestQueue requestQueue = new RequestQueue(cache, network);

        requestQueue.start();

        final JsonObjectRequest jsonObjectRequest = new JsonObjectRequest(Request.Method.GET, getString(R.string.project_website)+"api/1/android/message/?version_name="+version+"&device="+device, null, new Response.Listener<JSONObject>()
        {
            @Override
            public void onResponse(JSONObject response)
            {
                requestQueue.stop();

                try
                {
                    final long id = response.getLong("id");
                    final String title = response.getString("title");
                    final String message = response.getString("message");

                    final long lastId = mTools.getSharedPreferencesLong("LAST_MESSAGE_ID");

                    if(lastId == 0)
                    {
                        mTools.setSharedPreferencesLong("LAST_MESSAGE_ID", id);
                    }
                    else if(id != lastId)
                    {
                        new MaterialDialog.Builder(mContext).title(title).content(message).positiveText(R.string.main_message_dialog_positive_button).onPositive(new MaterialDialog.SingleButtonCallback()
                        {
                            @Override
                            public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
                            {
                                mTools.setSharedPreferencesLong("LAST_MESSAGE_ID", id);
                            }
                        }).contentColorRes(R.color.black).show();
                    }
                }
                catch(Exception e)
                {
                    Log.e("MainActivity", Log.getStackTraceString(e));
                }
            }
        }, new Response.ErrorListener()
        {
            @Override
            public void onErrorResponse(VolleyError error)
            {
                requestQueue.stop();

                Log.e("MainActivity", error.toString());
            }
        });

        jsonObjectRequest.setRetryPolicy(new DefaultRetryPolicy(10000, DefaultRetryPolicy.DEFAULT_MAX_RETRIES, DefaultRetryPolicy.DEFAULT_BACKOFF_MULT));

        requestQueue.add(jsonObjectRequest);

        // Permissions
        grantPermissions();

        // Google analytics
        final GoogleAnalytics googleAnalytics = GoogleAnalytics.getInstance(mContext);
        final Tracker tracker = googleAnalytics.newTracker(R.xml.app_tracker);
        tracker.send(new HitBuilders.ScreenViewBuilder().build());
	}

	// Resume activity
	@Override
	protected void onResume()
	{
		super.onResume();

        if(mGoogleApiClient != null) mGoogleApiClient.connect();

		listComputers();
	}

	// Destroy activity
	@Override
	protected void onDestroy()
	{
		super.onDestroy();

        if(mGoogleApiClient != null) mGoogleApiClient.disconnect();

		if(mCursor != null && !mCursor.isClosed()) mCursor.close();
		if(mDatabase != null && mDatabase.isOpen()) mDatabase.close();
    }

	// Menu
	@Override
	public boolean onCreateOptionsMenu(Menu menu)
	{
        getMenuInflater().inflate(R.menu.menu_main, menu);
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item)
	{
        switch(item.getItemId())
        {
            case R.id.main_menu_settings:
            {
                Intent intent = new Intent(mContext, SettingsActivity.class);
                startActivity(intent);
                return true;
            }
            case R.id.main_menu_make_donation:
            {
                Intent intent = new Intent(mContext, DonateActivity.class);
                startActivity(intent);
                return true;
            }
            case R.id.main_menu_troubleshooting:
            {
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse("http://www.olejon.net/code/spotcommander/?troubleshooting"));
                startActivity(intent);
                return true;
            }
            case R.id.main_menu_report_issue:
            {
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse("http://www.olejon.net/code/spotcommander/?issues"));
                startActivity(intent);
                return true;
            }
            case R.id.main_menu_privacy_policy:
            {
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(getString(R.string.project_privacy_policy)));
                startActivity(intent);
                return true;
            }
            default:
            {
                return super.onOptionsItemSelected(item);
            }
        }
	}

    // Computers
    private void listComputers()
    {
        final String[] queryColumns = {MainSQLiteHelper.COLUMN_ID, MainSQLiteHelper.COLUMN_NAME};
        mCursor = mDatabase.query(MainSQLiteHelper.TABLE_COMPUTERS, queryColumns, null, null, null, null, MainSQLiteHelper.COLUMN_NAME+" COLLATE NOCASE");

        final String[] fromColumns = {MainSQLiteHelper.COLUMN_NAME};
        final int[] toViews = {R.id.main_list_item};

        mListView.setAdapter(new SimpleCursorAdapter(mContext, R.layout.activity_main_list_item, mCursor, fromColumns, toViews, 0));

        mFloatingActionButton.startAnimation(AnimationUtils.loadAnimation(mContext, R.anim.fab));
        mFloatingActionButton.setVisibility(View.VISIBLE);
    }

    private void openComputer(final long id)
    {
        mTools.setSharedPreferencesLong("LAST_COMPUTER_ID", id);
        mTools.setSharedPreferencesString("LAST_NETWORK_ID", mTools.getCurrentNetwork());

        if(mGoogleApiClient != null && mGoogleApiClient.isConnected())
        {
            Wearable.NodeApi.getConnectedNodes(mGoogleApiClient).setResultCallback(new ResultCallback<NodeApi.GetConnectedNodesResult>()
            {
                @Override
                public void onResult(@NonNull NodeApi.GetConnectedNodesResult getConnectedNodesResult)
                {
                    mTools.setSharedPreferencesBoolean("WEAR_CONNECTED", (getConnectedNodesResult.getNodes().size() > 0));

                    Intent intent = new Intent(mContext, WebViewActivity.class);
                    startActivity(intent);
                }
            });
        }
        else
        {
            mTools.setSharedPreferencesBoolean("WEAR_CONNECTED", false);

            Intent intent = new Intent(mContext, WebViewActivity.class);
            startActivity(intent);
        }
    }

    private void removeComputer(final long id)
    {
        mDatabase.delete(MainSQLiteHelper.TABLE_COMPUTERS, MainSQLiteHelper.COLUMN_ID+" = "+id, null);
    }

    // Permissions
    private void grantPermissions()
    {
        if(ActivityCompat.checkSelfPermission(mContext, Manifest.permission.READ_PHONE_STATE) != PackageManager.PERMISSION_GRANTED)
        {
            String[] permissions = {Manifest.permission.READ_PHONE_STATE};

            ActivityCompat.requestPermissions(mActivity, permissions, PERMISSIONS_REQUEST_READ_PHONE_STATE);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults)
    {
        if(requestCode == PERMISSIONS_REQUEST_READ_PHONE_STATE && grantResults[0] != PackageManager.PERMISSION_GRANTED)
        {
            mTools.showToast(getString(R.string.main_permissions_not_granted), 1);
        }
    }
}