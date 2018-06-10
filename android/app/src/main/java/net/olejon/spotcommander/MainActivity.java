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
import android.content.DialogInterface;
import android.content.Intent;
import android.content.pm.ActivityInfo;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.os.Build;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.annotation.NonNull;
import android.support.design.widget.FloatingActionButton;
import android.support.v4.app.ActivityCompat;
import android.support.v4.widget.SimpleCursorAdapter;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.animation.AnimationUtils;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.AdapterView.OnItemLongClickListener;
import android.widget.ListView;

import com.afollestad.materialdialogs.DialogAction;
import com.afollestad.materialdialogs.MaterialDialog;
import com.google.firebase.analytics.FirebaseAnalytics;
import com.google.firebase.messaging.FirebaseMessaging;

public class MainActivity extends AppCompatActivity
{
	private final int PERMISSIONS_REQUEST_READ_PHONE_STATE = 0;

	private final Activity mActivity = this;

	private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private SQLiteDatabase mDatabase;
	private Cursor mCursor;

	private FloatingActionButton mFloatingActionButton;
	private ListView mListView;

	// Create activity
	@Override
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

		// Firebase
		FirebaseAnalytics.getInstance(mContext);
		FirebaseMessaging.getInstance().subscribeToTopic("message");

		// Settings
		PreferenceManager.setDefaultValues(mContext, R.xml.settings, false);

		// Allow landscape?
		if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

		// Database
		mDatabase = new MainSQLiteHelper(mContext).getWritableDatabase();

		// Layout
		setContentView(R.layout.activity_main);

		// Toolbar
		Toolbar toolbar = findViewById(R.id.main_toolbar);
		toolbar.setTitle(getString(R.string.main_title));

		setSupportActionBar(toolbar);

		// Listview
		mListView = findViewById(R.id.main_list);

		View listViewHeader = getLayoutInflater().inflate(R.layout.activity_main_subheader, mListView, false);
		mListView.addHeaderView(listViewHeader, null, false);

		View listViewEmpty = findViewById(R.id.main_empty);
		mListView.setEmptyView(listViewEmpty);

		mListView.setLongClickable(true);

		mListView.setOnItemClickListener(new OnItemClickListener()
		{
			@Override
			public void onItemClick(AdapterView<?> parent, View view, int position, long id)
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
				}).contentColorRes(R.color.black).positiveColorRes(R.color.dark_green).negativeColorRes(R.color.black).show();

				return true;
			}
		});

		// Floating action button
		mFloatingActionButton = findViewById(R.id.main_fab);

		mFloatingActionButton.setOnClickListener(new View.OnClickListener()
		{
			@Override
			public void onClick(View view)
			{
				Intent intent = new Intent(mContext, AddComputerActivity.class);
				startActivity(intent);
			}
		});

		// Information dialog
		if(Build.VERSION.SDK_INT < Build.VERSION_CODES.KITKAT)
		{
			if(!mTools.getSharedPreferencesBoolean("HIDE_INFORMATION_DIALOG_79"))
			{
				new MaterialDialog.Builder(mContext).title(R.string.main_information_dialog_title).content(getString(R.string.main_information_dialog_message)).positiveText(R.string.main_information_dialog_positive_button).onPositive(new MaterialDialog.SingleButtonCallback()
				{
					@Override
					public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
					{
						mTools.setSharedPreferencesBoolean("HIDE_INFORMATION_DIALOG_79", true);
					}
				}).contentColorRes(R.color.black).positiveColorRes(R.color.dark_green).show();
			}
		}

		// Permissions
		grantPermissions(true);
	}

	// Resume activity
	@Override
	protected void onResume()
	{
		super.onResume();

		listComputers();
	}

	// Destroy activity
	@Override
	protected void onDestroy()
	{
		super.onDestroy();

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
			case R.id.main_menu_troubleshooting:
			{
				mTools.openChromeCustomTabsUri(getString(R.string.project_developer_troubleshooting_uri));
				return true;
			}
			case R.id.main_menu_report_issue:
			{
				mTools.openChromeCustomTabsUri(getString(R.string.project_developer_issues_uri));
				return true;
			}
			case R.id.main_menu_privacy_policy:
			{
				mTools.openChromeCustomTabsUri(getString(R.string.project_developer_privacy_uri));
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
		String[] queryColumns = {MainSQLiteHelper.COLUMN_ID, MainSQLiteHelper.COLUMN_NAME};
		mCursor = mDatabase.query(MainSQLiteHelper.TABLE_COMPUTERS, queryColumns, null, null, null, null, MainSQLiteHelper.COLUMN_NAME+" COLLATE NOCASE");

		String[] fromColumns = {MainSQLiteHelper.COLUMN_NAME};
		int[] toViews = {R.id.main_list_item};

		mListView.setAdapter(new SimpleCursorAdapter(mContext, R.layout.activity_main_list_item, mCursor, fromColumns, toViews, 0));

		mFloatingActionButton.startAnimation(AnimationUtils.loadAnimation(mContext, R.anim.fab));
		mFloatingActionButton.setVisibility(View.VISIBLE);
	}

	private void openComputer(long id)
	{
		mTools.setSharedPreferencesLong("LAST_COMPUTER_ID", id);
		mTools.setSharedPreferencesString("LAST_NETWORK_ID", mTools.getCurrentNetwork());

		Intent intent = new Intent(mContext, WebViewActivity.class);
		startActivity(intent);
	}

	private void removeComputer(long id)
	{
		mDatabase.delete(MainSQLiteHelper.TABLE_COMPUTERS, MainSQLiteHelper.COLUMN_ID+" = "+id, null);
	}

	// Permissions
	private void grantPermissions(boolean showDialog)
	{
		if(showDialog && ActivityCompat.checkSelfPermission(mContext, Manifest.permission.READ_PHONE_STATE) != PackageManager.PERMISSION_GRANTED)
		{
			new MaterialDialog.Builder(mContext).title(R.string.main_permissions_dialog_title).content(R.string.main_permissions_dialog_message).positiveText(R.string.main_permissions_dialog_positive_button).onPositive(new MaterialDialog.SingleButtonCallback()
			{
				@Override
				public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
				{
					grantPermissions(false);
				}
			}).cancelListener(new DialogInterface.OnCancelListener()
			{
				@Override
				public void onCancel(DialogInterface dialogInterface)
				{
					grantPermissions(false);
				}
			}).contentColorRes(R.color.black).positiveColorRes(R.color.dark_green).show();
		}
		else
		{
			String[] permissions = {Manifest.permission.READ_PHONE_STATE};

			ActivityCompat.requestPermissions(mActivity, permissions, PERMISSIONS_REQUEST_READ_PHONE_STATE);
		}
	}

	@Override
	public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults)
	{
		if(requestCode == PERMISSIONS_REQUEST_READ_PHONE_STATE && grantResults[0] != PackageManager.PERMISSION_GRANTED) mTools.showToast(getString(R.string.main_permissions_not_granted), 1);
	}
}