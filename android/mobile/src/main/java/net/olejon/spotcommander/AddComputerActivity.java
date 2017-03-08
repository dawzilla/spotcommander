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

import android.content.ContentValues;
import android.content.Context;
import android.content.pm.ActivityInfo;
import android.database.sqlite.SQLiteDatabase;
import android.net.wifi.WifiInfo;
import android.net.wifi.WifiManager;
import android.os.AsyncTask;
import android.os.Bundle;
import android.os.PowerManager;
import android.support.annotation.NonNull;
import android.support.design.widget.TextInputLayout;
import android.support.v4.app.NavUtils;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.text.Editable;
import android.text.TextWatcher;
import android.text.method.LinkMovementMethod;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.inputmethod.InputMethodManager;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;

import com.afollestad.materialdialogs.DialogAction;
import com.afollestad.materialdialogs.MaterialDialog;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

public class AddComputerActivity extends AppCompatActivity
{
    private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private PowerManager.WakeLock mWakeLock;

	private MenuItem mScanNetworkMenuItem;
	private ProgressBar mProgressBar;
    private TextInputLayout mAddComputerNameInputLayout;
    private TextInputLayout mAddComputerUriInputLayout;

    private NetworkScanTask mNetworkScanTask;

	// Create activity
	@Override
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

        // Power manager
        final PowerManager powerManager = (PowerManager) getSystemService(Context.POWER_SERVICE);

        //noinspection deprecation
        mWakeLock = powerManager.newWakeLock(PowerManager.SCREEN_DIM_WAKE_LOCK, "wakeLock");

		// Allow landscape?
		if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

		// Layout
		setContentView(R.layout.activity_add_computer);

        // Toolbar
        final Toolbar toolbar = (Toolbar) findViewById(R.id.add_computer_toolbar);
        toolbar.setTitleTextColor(ContextCompat.getColor(mContext, R.color.white));

        setSupportActionBar(toolbar);
        if(getSupportActionBar() != null) getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        mAddComputerNameInputLayout = (TextInputLayout) findViewById(R.id.add_computer_text_input_name_layout);
        mAddComputerUriInputLayout = (TextInputLayout) findViewById(R.id.add_computer_text_input_uri_layout);
        mAddComputerNameInputLayout.setHintAnimationEnabled(true);
        mAddComputerUriInputLayout.setHintAnimationEnabled(true);

		// Progress bar
        mProgressBar = (ProgressBar) findViewById(R.id.add_computer_progressbar);

        // Information
        final TextView textView = (TextView) findViewById(R.id.add_computer_information);
        textView.setMovementMethod(LinkMovementMethod.getInstance());

        // Scan dialog
        new MaterialDialog.Builder(mContext).title(R.string.add_computer_scan_dialog_title).content(getString(R.string.add_computer_scan_dialog_message)).positiveText(R.string.add_computer_scan_dialog_positive_button).negativeText(R.string.add_computer_scan_dialog_negative_button).onPositive(new MaterialDialog.SingleButtonCallback()
        {
            @Override
            public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
            {
                scanNetwork();
            }
        }).contentColorRes(R.color.black).negativeColorRes(R.color.black).show();
	}

	// Pause activity
	@Override
	protected void onPause()
	{
		super.onPause();

		if(mNetworkScanTask != null && mNetworkScanTask.getStatus() == AsyncTask.Status.RUNNING) mNetworkScanTask.cancel(true);

		if(mWakeLock.isHeld()) mWakeLock.release();
	}

	// Menu
	@Override
	public boolean onCreateOptionsMenu(Menu menu)
	{
		getMenuInflater().inflate(R.menu.menu_add_computer, menu);

        mScanNetworkMenuItem = menu.findItem(R.id.add_computer_menu_scan_network);

		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item)
	{
        switch(item.getItemId())
        {
            case android.R.id.home:
            {
                NavUtils.navigateUpFromSameTask(this);
                return true;
            }
            case R.id.add_computer_menu_scan_network:
            {
                scanNetwork();
                return true;
            }
            case R.id.add_computer_menu_add_computer:
            {
                addComputer();
                return true;
            }
            default:
            {
                return super.onOptionsItemSelected(item);
            }
        }
	}

	// Add computer
	private void addComputer()
	{
    	final EditText computerNameInput = (EditText) findViewById(R.id.add_computer_name);
    	final EditText computerUriInput = (EditText) findViewById(R.id.add_computer_uri);
    	final EditText computerUsernameInput = (EditText) findViewById(R.id.add_computer_username);
    	final EditText computerPasswordInput = (EditText) findViewById(R.id.add_computer_password);

        computerNameInput.addTextChangedListener(new TextWatcher()
        {
            @Override
            public void onTextChanged(CharSequence charSequence, int i, int i1, int i2)
            {
                mAddComputerNameInputLayout.setError(null);
            }

            @Override
            public void beforeTextChanged(CharSequence charSequence, int i, int i1, int i2) { }

            @Override
            public void afterTextChanged(Editable editable) { }
        });

        computerUriInput.addTextChangedListener(new TextWatcher()
        {
            @Override
            public void onTextChanged(CharSequence charSequence, int i, int i1, int i2)
            {
                mAddComputerUriInputLayout.setError(null);
            }

            @Override
            public void beforeTextChanged(CharSequence charSequence, int i, int i1, int i2) { }

            @Override
            public void afterTextChanged(Editable editable) { }
        });

    	final String computerName = computerNameInput.getText().toString().trim();
    	final String computerUri = computerUriInput.getText().toString().trim();
    	final String computerUsername = computerUsernameInput.getText().toString().trim();
    	final String computerPassword = computerPasswordInput.getText().toString().trim();

        final InputMethodManager inputMethodManager = (InputMethodManager) getSystemService(Context.INPUT_METHOD_SERVICE);
        inputMethodManager.hideSoftInputFromWindow(computerNameInput.getWindowToken(), InputMethodManager.HIDE_IMPLICIT_ONLY);

    	if(mNetworkScanTask != null && mNetworkScanTask.getStatus() == AsyncTask.Status.RUNNING)
    	{
    		mTools.showToast(getString(R.string.add_computer_scanning_network), 0);
    	}
    	else if(computerName.equals(""))
    	{
            mAddComputerNameInputLayout.setError(getString(R.string.add_computer_invalid_name));
    	}
        else if(!computerUri.matches("^https?://.*"))
        {
            mAddComputerUriInputLayout.setError(getString(R.string.add_computer_invalid_uri));
        }
    	else
    	{
            final ContentValues contentValues = new ContentValues();

    		contentValues.put(MainSQLiteHelper.COLUMN_NAME, computerName);
    		contentValues.put(MainSQLiteHelper.COLUMN_URI, computerUri);
    		contentValues.put(MainSQLiteHelper.COLUMN_USERNAME, computerUsername);
    		contentValues.put(MainSQLiteHelper.COLUMN_PASSWORD, computerPassword);
            contentValues.put(MainSQLiteHelper.COLUMN_NETWORK_NAME, "");
            contentValues.put(MainSQLiteHelper.COLUMN_NETWORK_DEFAULT, 0);

    		final SQLiteDatabase database = new MainSQLiteHelper(mContext).getWritableDatabase();

    		database.insert(MainSQLiteHelper.TABLE_COMPUTERS, null, contentValues);

    		database.close();

            finish();
    	}
	}

	// Scan network
	private void scanNetwork()
	{
		if(mNetworkScanTask != null && mNetworkScanTask.getStatus() == AsyncTask.Status.RUNNING)
		{
            mNetworkScanTask.cancel(true);
		}
		else
		{
			final WifiManager wifiManager = (WifiManager) getApplicationContext().getSystemService(WIFI_SERVICE);

			if(wifiManager.isWifiEnabled())
			{	
				final WifiInfo wifiInfo = wifiManager.getConnectionInfo();

				final int wifiIpAddress = wifiInfo.getIpAddress();

				final String wifiSubnet = String.format(Locale.US, "%d.%d.%d", (wifiIpAddress & 0xff), (wifiIpAddress >> 8 & 0xff), (wifiIpAddress >> 16 & 0xff));

				if(wifiSubnet.equals("0.0.0"))
				{
					mTools.showToast(getString(R.string.add_computer_wifi_not_connected), 0);
				}
				else
				{
                    mNetworkScanTask = new NetworkScanTask();
                    mNetworkScanTask.execute(wifiSubnet);
				}
			}
			else
			{
				mTools.showToast(getString(R.string.add_computer_wifi_not_connected), 0);
			}
		}
	}

    public class NetworkScanTask extends AsyncTask<String, String, String[]>
	{
    	final EditText computerNameInput = (EditText) findViewById(R.id.add_computer_name);
    	final EditText computerUriInput = (EditText) findViewById(R.id.add_computer_uri);

        @Override
        protected void onPreExecute()
        {
        	if(!mWakeLock.isHeld()) mWakeLock.acquire();

            mScanNetworkMenuItem.setTitle(getString(R.string.add_computer_menu_stop));

            mProgressBar.setVisibility(View.VISIBLE);

            computerNameInput.setEnabled(false);
            computerNameInput.setText(getString(R.string.add_computer_scanning), TextView.BufferType.EDITABLE);

            computerUriInput.setEnabled(false);
        }

        @Override
        protected void onProgressUpdate(String... strings)
        {
            computerUriInput.setText(getString(R.string.add_computer_trying, strings[0]), TextView.BufferType.EDITABLE);
        }

        @Override
        protected void onPostExecute(String[] string)
        {
            mScanNetworkMenuItem.setTitle(getString(R.string.add_computer_menu_scan));

            mProgressBar.setVisibility(View.GONE);

            computerNameInput.setEnabled(true);
            computerUriInput.setEnabled(true);

        	final String computerIpAddress = string[0];
        	final String computerHostname = (string[1].equals("")) ? getString(R.string.add_computer_unknown) : string[1];

        	if(computerIpAddress.equals(""))
        	{
                computerNameInput.setText(getString(R.string.add_computer_name_text), TextView.BufferType.EDITABLE);
                computerUriInput.setText(getString(R.string.add_computer_uri_text), TextView.BufferType.EDITABLE);

        		mTools.showToast(getString(R.string.add_computer_not_found), 1);
        	}
        	else
        	{
                computerNameInput.setText(computerHostname, TextView.BufferType.EDITABLE);
                computerUriInput.setText(getString(R.string.add_computer_uri, computerIpAddress), TextView.BufferType.EDITABLE);

        		final String computerFound = (computerHostname.equals(getString(R.string.add_computer_computer))) ? getString(R.string.add_computer_found_with_authentication) : computerHostname;

        		mTools.showToast(getString(R.string.add_computer_found, computerFound), 1);
        	}
        }

        @Override
        protected void onCancelled()
        {
        	if(mWakeLock.isHeld()) mWakeLock.release();

            mScanNetworkMenuItem.setTitle(getString(R.string.add_computer_menu_scan));

            mProgressBar.setVisibility(View.GONE);

            computerNameInput.setEnabled(true);
            computerNameInput.setText(getString(R.string.add_computer_name_text), TextView.BufferType.EDITABLE);

            computerUriInput.setEnabled(true);
            computerUriInput.setText(getString(R.string.add_computer_uri_text), TextView.BufferType.EDITABLE);
        }

        @Override
        protected String[] doInBackground(String... strings)
        {
        	final String computerSubnet = strings[0];

            final String[] computerScanResult = {"", ""};

            outerLoop: for(int i = 1; i <= 254; i++)
            {
                if(isCancelled()) break;

                String computerIpAddress = computerSubnet+"."+i;

                publishProgress(computerIpAddress);

                HttpURLConnection httpURLConnection = null;

                try
                {
                    URL url = new URL("http://"+computerIpAddress+"/spotcommander/main.php?hostname");

                    httpURLConnection = (HttpURLConnection) url.openConnection();

                    httpURLConnection.setConnectTimeout(500);
                    httpURLConnection.setReadTimeout(2500);

                    BufferedReader bufferedReader = new BufferedReader(new InputStreamReader(new BufferedInputStream(httpURLConnection.getInputStream())));

                    StringBuilder stringBuilder = new StringBuilder();

                    String bufferedReaderLine;

                    while((bufferedReaderLine = bufferedReader.readLine()) != null)
                    {
                        stringBuilder.append(bufferedReaderLine);
                    }

                    String computerHostname = stringBuilder.toString();

                    Map<String, List<String>> headerFields = httpURLConnection.getHeaderFields();
                    Set<String> headerFieldsKeys = headerFields.keySet();

                    for(String headerKey : headerFieldsKeys)
                    {
                        String headerString = headerFields.get(headerKey).toString();

                        if(headerString.contains("TP-LINK") || headerString.contains("ZyXEL")) continue outerLoop;
                    }

                    if(computerHostname.contains("html")) continue;

                    computerScanResult[0] = computerIpAddress;
                    computerScanResult[1] = computerHostname;

                    break;
                }
                catch(Exception e1)
                {
                    try
                    {
                        if(httpURLConnection != null)
                        {
                            if(httpURLConnection.getResponseCode() == 401)
                            {
                                if(!httpURLConnection.getHeaderField("WWW-Authenticate").contains(getString(R.string.project_name))) continue;

                                computerScanResult[0] = computerIpAddress;
                                computerScanResult[1] = "Computer";

                                break;
                            }
                        }
                    }
                    catch(Exception e2)
                    {
                        Log.w("AddComputerActivity", computerIpAddress);
                    }
                }
                finally
                {
                    if(httpURLConnection != null) httpURLConnection.disconnect();
                }
            }

            return computerScanResult;
        }
    }
}