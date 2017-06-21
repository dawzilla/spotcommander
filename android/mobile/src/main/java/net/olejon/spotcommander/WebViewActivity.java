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
import android.app.Notification;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.pm.ActivityInfo;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.net.http.SslError;
import android.os.AsyncTask;
import android.os.Build;
import android.os.Bundle;
import android.os.PowerManager;
import android.support.annotation.NonNull;
import android.support.v4.app.NotificationCompat;
import android.support.v4.app.NotificationManagerCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.graphics.Palette;
import android.util.Log;
import android.view.KeyEvent;
import android.view.WindowManager;
import android.webkit.CookieSyncManager;
import android.webkit.HttpAuthHandler;
import android.webkit.JavascriptInterface;
import android.webkit.SslErrorHandler;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import com.afollestad.materialdialogs.DialogAction;
import com.afollestad.materialdialogs.MaterialDialog;
import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.common.api.ResultCallback;
import com.google.android.gms.wearable.NodeApi;
import com.google.android.gms.wearable.Wearable;

import org.json.JSONArray;

import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Arrays;

public class WebViewActivity extends AppCompatActivity
{
	public final static int NOTIFICATION_ID = 1;

    private final Activity mActivity = this;

    private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

    private GoogleApiClient mGoogleApiClient;
	private PowerManager.WakeLock mWakeLock;
    private NotificationManagerCompat mNotificationManager;
	private NotificationCompat.Builder mNotificationBuilder;
	private WebView mWebView;

    private PendingIntent mHidePendingIntent;
    private PendingIntent mPreviousPendingIntent;
    private PendingIntent mPlayPausePendingIntent;
    private PendingIntent mVolumeDownPendingIntent;
    private PendingIntent mVolumeUpPendingIntent;
    private PendingIntent mNextPendingIntent;

	private String mProjectVersionName;
	private String mCurrentNetwork;

    private boolean mActivityIsPaused = false;
	private boolean mHasLongPressedBack = false;
	private boolean mPersistentNotificationIsSupported = false;

    private int mStatusBarPrimaryColor = 0;
    private int mStatusBarCoverArtColor = 0;

	// Create activity
    protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

        // Google API client
        mGoogleApiClient = new GoogleApiClient.Builder(mContext).addApiIfAvailable(Wearable.API).build();

		// Allow landscape?
		if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

		// Hide status bar?
		if(mTools.getDefaultSharedPreferencesBoolean("HIDE_STATUS_BAR")) getWindow().setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN, WindowManager.LayoutParams.FLAG_FULLSCREEN);

		// Power manager
		final PowerManager powerManager = (PowerManager) getSystemService(Context.POWER_SERVICE);

        //noinspection deprecation
        mWakeLock = powerManager.newWakeLock(PowerManager.SCREEN_DIM_WAKE_LOCK, "wakeLock");

		// Settings
        mTools.setSharedPreferencesBoolean("CAN_CLOSE_COVER", false);

		// Current network
        mCurrentNetwork = mTools.getCurrentNetwork();

		// Computer
		final long computerId = mTools.getSharedPreferencesLong("LAST_COMPUTER_ID");

        if(computerId == 0)
        {
            mTools.showToast(getString(R.string.webview_no_computer_added_error), 1);

            finish();

            return;
        }

		final String[] computer = mTools.getComputer(computerId);

		final String uri = computer[0];
		final String username = computer[1];
		final String password = computer[2];

		// Layout
		setContentView(R.layout.activity_webview);

        // Status bar color
        if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
        {
            mStatusBarPrimaryColor = getWindow().getStatusBarColor();
            mStatusBarCoverArtColor = mStatusBarPrimaryColor;
        }

		// Notification
        mPersistentNotificationIsSupported = (Build.VERSION.SDK_INT >= Build.VERSION_CODES.JELLY_BEAN);

		if(mPersistentNotificationIsSupported)
		{
			final Intent launchActivityIntent = new Intent(mContext, MainActivity.class);
			launchActivityIntent.setAction("android.intent.action.MAIN");
			launchActivityIntent.addCategory("android.intent.category.LAUNCHER");
            PendingIntent launchActivityPendingIntent = PendingIntent.getActivity(mContext, 0, launchActivityIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent hideIntent = new Intent(mContext, RemoteControlIntentService.class);
			hideIntent.setAction("hide_notification");
			hideIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
			mHidePendingIntent = PendingIntent.getService(mContext, 0, hideIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent previousIntent = new Intent(mContext, RemoteControlIntentService.class);
            previousIntent.setAction("previous");
            previousIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
            mPreviousPendingIntent = PendingIntent.getService(mContext, 0, previousIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent playPauseIntent = new Intent(mContext, RemoteControlIntentService.class);
			playPauseIntent.setAction("play_pause");
			playPauseIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
			mPlayPausePendingIntent = PendingIntent.getService(mContext, 0, playPauseIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent nextIntent = new Intent(mContext, RemoteControlIntentService.class);
			nextIntent.setAction("next");
			nextIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
			mNextPendingIntent = PendingIntent.getService(mContext, 0, nextIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent volumeDownIntent = new Intent(mContext, RemoteControlIntentService.class);
            volumeDownIntent.setAction("adjust_volume_down");
            volumeDownIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
            mVolumeDownPendingIntent = PendingIntent.getService(mContext, 0, volumeDownIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent volumeUpIntent = new Intent(mContext, RemoteControlIntentService.class);
            volumeUpIntent.setAction("adjust_volume_up");
            volumeUpIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
            mVolumeUpPendingIntent = PendingIntent.getService(mContext, 0, volumeUpIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            mNotificationManager = NotificationManagerCompat.from(mContext);
            mNotificationBuilder = new NotificationCompat.Builder(mContext);

            mNotificationBuilder.setWhen(0)
                    .setSmallIcon(R.drawable.ic_play_arrow_white_24dp)
                    .setLargeIcon(BitmapFactory.decodeResource(getResources(), R.mipmap.ic_launcher))
                    .setContentTitle(getString(R.string.project_name))
                    .setContentIntent(launchActivityPendingIntent);

            if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
            {
                mNotificationBuilder.setCategory(Notification.CATEGORY_TRANSPORT)
                        .setPriority(Notification.PRIORITY_MAX)
                        .setVisibility(Notification.VISIBILITY_PUBLIC);
            }
		}

		// Web view
        mWebView = (WebView) findViewById(R.id.webview_webview);

        mWebView.setBackgroundColor(ContextCompat.getColor(mContext, R.color.background));
        mWebView.setScrollBarStyle(WebView.SCROLLBARS_INSIDE_OVERLAY);

        mWebView.setWebViewClient(new WebViewClient()
		{
			@SuppressWarnings("deprecation")
            @Override
			public boolean shouldOverrideUrlLoading(WebView view, String url)
			{
				if(url != null && !url.contains(uri) && !url.contains("olejon.net") && !url.contains("spotify.com") && !url.contains("facebook.com"))
				{
                    mTools.openChromeCustomTabsUri(url);

					return true;
				}

				return false;
			}

			@Override
			public void onReceivedHttpAuthRequest(WebView view, @NonNull HttpAuthHandler handler, String host, String realm)
			{	
				if(handler.useHttpAuthUsernamePassword())
				{
					handler.proceed(username, password);
				}
				else
				{
					handler.cancel();

                    mWebView.stopLoading();

                    mTools.showToast(getString(R.string.webview_authentication_failed), 1);

                    mTools.navigateUp(mActivity);
				}
			}

			@Override
			public void onReceivedError(WebView view, WebResourceRequest webResourceRequest, WebResourceError webResourceError)
			{
                mWebView.stopLoading();

                mTools.showToast(getString(R.string.webview_on_received_error), 1);

                mTools.navigateUp(mActivity);
			}

            @Override
            public void onReceivedSslError(WebView view, @NonNull SslErrorHandler handler, SslError error)
            {
                handler.cancel();

                mWebView.stopLoading();

                new MaterialDialog.Builder(mContext).title(R.string.webview_dialog_ssl_error_title).content(getString(R.string.webview_dialog_ssl_error_message)).positiveText(R.string.webview_dialog_ssl_error_positive_button).onPositive(new MaterialDialog.SingleButtonCallback()
                {
                    @Override
                    public void onClick(@NonNull MaterialDialog materialDialog, @NonNull DialogAction dialogAction)
                    {
                        finish();
                    }
                }).contentColorRes(R.color.black).positiveColorRes(R.color.dark_green).show();
            }
		});

		// User agent
        mProjectVersionName = mTools.getProjectVersionName();

        final String userAgentAppend1 = (!username.equals("") && !password.equals("")) ? "AUTHENTICATION_ENABLED" : "";
        final String userAgentAppend2 = (Build.VERSION.SDK_INT < Build.VERSION_CODES.KITKAT && !mTools.getDefaultSharedPreferencesBoolean("HARDWARE_ACCELERATED_ANIMATIONS")) ? "DISABLE_CSSTRANSITIONS DISABLE_CSSTRANSFORMS3D" : "";

		// Web settings
		final WebSettings webSettings = mWebView.getSettings();
		webSettings.setJavaScriptEnabled(true);
		webSettings.setSupportZoom(false);
		webSettings.setUserAgentString(getString(R.string.webview_user_agent, webSettings.getUserAgentString(), mProjectVersionName, userAgentAppend1, userAgentAppend2));

		// Load app
		if(savedInstanceState != null)
		{
            mWebView.restoreState(savedInstanceState);
		}
		else
		{
            mWebView.loadUrl(uri);
		}

		// JavaScript interface
        mWebView.addJavascriptInterface(new JavaScriptInterface(), "Android");
	}

	// Pause activity
	@Override
	public void onPause()
	{
		super.onPause();

        mActivityIsPaused = true;

		if(mWakeLock.isHeld()) mWakeLock.release();

        if(!mHasLongPressedBack)
        {
            final String nowplaying_artist = mTools.getSharedPreferencesString("NOWPLAYING_ARTIST");
            final String nowplaying_title = mTools.getSharedPreferencesString("NOWPLAYING_TITLE");

            if(!nowplaying_artist.equals(getString(R.string.notification_no_music_is_playing_artist)))
            {
                if(mPersistentNotificationIsSupported && mGoogleApiClient != null && mGoogleApiClient.isConnected())
                {
                    Wearable.NodeApi.getConnectedNodes(mGoogleApiClient).setResultCallback(new ResultCallback<NodeApi.GetConnectedNodesResult>()
                    {
                        @Override
                        public void onResult(@NonNull NodeApi.GetConnectedNodesResult getConnectedNodesResult)
                        {
                            if(getConnectedNodesResult.getNodes().size() > 0)
                            {
                                mNotificationBuilder.setContentText(getString(R.string.notification_wear_text))
                                        .addAction(R.drawable.ic_skip_previous_white_24dp, getString(R.string.notification_action_previous), mPreviousPendingIntent)
                                        .addAction(R.drawable.ic_play_arrow_white_24dp, getString(R.string.notification_action_play_pause), mPlayPausePendingIntent)
                                        .addAction(R.drawable.ic_skip_next_white_24dp, getString(R.string.notification_action_next), mNextPendingIntent)
                                        .extend(new NotificationCompat.WearableExtender()
                                                .setBackground(BitmapFactory.decodeResource(getResources(), R.drawable.notification_background))
                                                .setContentAction(0)
                                                .setContentIcon(R.drawable.notification_icon)
                                                .addAction(new NotificationCompat.Action.Builder(R.drawable.ic_play_arrow_white_24dp, getString(R.string.notification_action_play_pause), mPlayPausePendingIntent).build())
                                                .addAction(new NotificationCompat.Action.Builder(R.drawable.ic_skip_previous_white_24dp, getString(R.string.notification_action_previous), mPreviousPendingIntent).build())
                                                .addAction(new NotificationCompat.Action.Builder(R.drawable.ic_skip_next_white_24dp, getString(R.string.notification_action_next), mNextPendingIntent).build())
                                                .addAction(new NotificationCompat.Action.Builder(R.drawable.ic_volume_down_white_24dp, getString(R.string.notification_action_volume_down), mVolumeDownPendingIntent).build())
                                                .addAction(new NotificationCompat.Action.Builder(R.drawable.ic_volume_up_white_24dp, getString(R.string.notification_action_volume_up), mVolumeUpPendingIntent).build())
                                        );

                                mNotificationManager.notify(NOTIFICATION_ID, mNotificationBuilder.build());
                            }
                        }
                    });
                }
                else if(mPersistentNotificationIsSupported && mTools.getSharedPreferencesBoolean("PERSISTENT_NOTIFICATION"))
                {
                    mNotificationBuilder.setContentText(getString(R.string.notification_mobile_text))
                            .setOngoing(true)
                            .setTicker(nowplaying_artist+" - "+nowplaying_title)
                            .addAction(R.drawable.ic_close_white_24dp, getString(R.string.notification_action_hide), mHidePendingIntent)
                            .addAction(R.drawable.ic_play_arrow_white_24dp, getString(R.string.notification_action_play_pause), mPlayPausePendingIntent)
                            .addAction(R.drawable.ic_skip_next_white_24dp, getString(R.string.notification_action_next), mNextPendingIntent);

                    mNotificationManager.notify(NOTIFICATION_ID, mNotificationBuilder.build());
                }
            }
        }

        if(Build.VERSION.SDK_INT < Build.VERSION_CODES.LOLLIPOP)
        {
            //noinspection deprecation
            CookieSyncManager.getInstance().sync();
        }

        mWebView.pauseTimers();
	}

	// Resume activity
	@Override
	public void onResume()
	{
		super.onResume();

        if(mGoogleApiClient != null) mGoogleApiClient.connect();

		if(mPersistentNotificationIsSupported) mNotificationManager.cancel(NOTIFICATION_ID);

		if(mCurrentNetwork.equals(mTools.getCurrentNetwork()))
		{
            mWebView.resumeTimers();

			if(mActivityIsPaused) mWebView.loadUrl("javascript:nativeAppLoad(true)");
		}
		else
		{
            mTools.showToast(getString(R.string.webview_network_changed), 1);

            mTools.navigateUp(mActivity);
		}
	}

    @Override
    protected void onSaveInstanceState(@NonNull Bundle outState)
    {
        super.onSaveInstanceState(outState);

        mWebView.saveState(outState);
    }

    // Destroy activity
	@Override
	protected void onDestroy()
	{
		super.onDestroy();

        if(mGoogleApiClient != null) mGoogleApiClient.disconnect();
	}

	// Key down
	@Override
	public boolean onKeyDown(int keyCode, @NonNull KeyEvent event)
	{
        switch(keyCode)
        {
            case KeyEvent.KEYCODE_BACK:
            {
                event.startTracking();
                return true;
            }
            case KeyEvent.KEYCODE_MENU:
            {
                return true;
            }
            case KeyEvent.KEYCODE_SEARCH:
            {
                return true;
            }
            case KeyEvent.KEYCODE_VOLUME_DOWN:
            {
                return true;
            }
            case KeyEvent.KEYCODE_VOLUME_UP:
            {
                return true;
            }
            default:
            {
                return super.onKeyDown(keyCode, event);
            }
        }
	}

	@Override
	public boolean onKeyLongPress(int keyCode, KeyEvent event)
	{
        switch(keyCode)
        {
            case KeyEvent.KEYCODE_BACK:
            {
                mHasLongPressedBack = true;

                mTools.navigateUp(mActivity);

                return true;
            }
            default:
            {
                return super.onKeyLongPress(keyCode, event);
            }
        }
	}

	// Key up
	@Override
	public boolean onKeyUp(int keyCode, @NonNull KeyEvent event)
	{
        switch(keyCode)
        {
            case KeyEvent.KEYCODE_MENU:
            {
                mWebView.loadUrl("javascript:nativeAppAction('menu')");
                return true;
            }
            case KeyEvent.KEYCODE_SEARCH:
            {
                mWebView.loadUrl("javascript:nativeAppAction('search')");
                return true;
            }
            case KeyEvent.KEYCODE_BACK:
            {
                if(mHasLongPressedBack) return true;

                if(mWebView.canGoBack() && mWebView.getUrl().contains("spotify.com") || mWebView.canGoBack() && mWebView.getUrl().contains("facebook.com"))
                {
                    mWebView.goBack();

                    return true;
                }
                else if(mWebView.canGoBack() || mTools.getSharedPreferencesBoolean("CAN_CLOSE_COVER"))
                {
                    mWebView.loadUrl("javascript:nativeAppAction('back')");

                    return true;
                }

                mTools.showToast(getString(R.string.webview_back), 1);

                return true;
            }
            case KeyEvent.KEYCODE_VOLUME_DOWN:
            {
                mWebView.loadUrl("javascript:nativeAppAction('volume_down')");
                return true;
            }
            case KeyEvent.KEYCODE_VOLUME_UP:
            {
                mWebView.loadUrl("javascript:nativeAppAction('volume_up')");
                return true;
            }
            default:
            {
                return super.onKeyUp(keyCode, event);
            }
        }
	}

	// JavaScript interface
	private class JavaScriptInterface
	{
        @JavascriptInterface
        public void JSstartService()
        {
            final Intent startServiceIntent = new Intent(mContext, RemoteControlService.class);

            if(mTools.getSharedPreferencesBoolean("PAUSE_ON_INCOMING_CALL") || mTools.getSharedPreferencesBoolean("PAUSE_ON_OUTGOING_CALL") || mTools.getSharedPreferencesBoolean("FLIP_TO_PAUSE") || mTools.getSharedPreferencesBoolean("SHAKE_TO_SKIP"))
            {
                startService(startServiceIntent);
            }
            else
            {
                stopService(startServiceIntent);
            }
        }

        @JavascriptInterface
        public String JSgetVersions()
        {
            return new JSONArray(Arrays.asList(mProjectVersionName, getString(R.string.project_minimum_version))).toString();
        }

        @JavascriptInterface
        public String JSgetSharedString(String preference)
        {
            return mTools.getSharedPreferencesString(preference);
        }

        @JavascriptInterface
        public void JSsetSharedString(String preference, String string)
        {
            mTools.setSharedPreferencesString(preference, string);
        }

        @JavascriptInterface
        public boolean JSgetSharedBoolean(String preference)
        {
            return mTools.getSharedPreferencesBoolean(preference);
        }

        @JavascriptInterface
        public void JSsetSharedBoolean(String preference, boolean bool)
        {
            mTools.setSharedPreferencesBoolean(preference, bool);
        }

        @JavascriptInterface
        public void JSkeepScreenOn(boolean keepScreenOn)
        {
            if(keepScreenOn)
            {
                if(!mWakeLock.isHeld()) mWakeLock.acquire();
            }
            else
            {
                if(mWakeLock.isHeld()) mWakeLock.release();
            }
        }

        @JavascriptInterface
        public void JSfinishActivity()
        {
            mHasLongPressedBack = true;

            mTools.navigateUp(mActivity);
        }

        @JavascriptInterface
        public int JSsearchApp(String searchApp, String string)
        {
            int searchAppFound;

            try
            {
                getPackageManager().getApplicationInfo(searchApp, 0);

                final Intent intent = new Intent(Intent.ACTION_SEARCH);
                intent.setPackage(searchApp);
                intent.putExtra("query", string);
                startActivity(intent);

                searchAppFound = 1;
            }
            catch(Exception e)
            {
                searchAppFound = 0;

                Log.e("WebViewActivity", Log.getStackTraceString(e));
            }

            return searchAppFound;
        }

        @JavascriptInterface
        public void JSshare(String title, String uri)
        {
            final Intent intent = new Intent();
            intent.setAction(Intent.ACTION_SEND);
            intent.putExtra(Intent.EXTRA_TEXT, uri);
            intent.setType("text/plain");

            final Intent chooserIntent = Intent.createChooser(intent, title);
            startActivity(chooserIntent);
        }

        @JavascriptInterface
        public void JSmakeDonation()
        {
            final Intent intent = new Intent(mContext, DonateActivity.class);
            startActivity(intent);
        }

	    @JavascriptInterface
	    public void JSopenUri(String uri)
	    {
            mTools.openChromeCustomTabsUri(uri);
	    }

        @JavascriptInterface
        public void JSsetStatusBarColor(String color)
        {
            final int intColor;

            switch(color)
            {
                case "primary":
                {
                    intColor = mStatusBarPrimaryColor;
                    break;
                }
                case "cover_art":
                {
                    intColor = mStatusBarCoverArtColor;
                    break;
                }
                default:
                {
                    intColor = Color.parseColor(color);
                    break;
                }
            }

            runOnUiThread(new Runnable()
            {
                @Override
                public void run()
                {
                    if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) getWindow().setStatusBarColor(intColor);
                }
            });
        }

        @JavascriptInterface
        public void JSsetStatusBarColorFromImage(String uri)
        {
            final GetStatusBarColorFromImageTask getStatusBarColorFromImageTask = new GetStatusBarColorFromImageTask();
            getStatusBarColorFromImageTask.execute(uri);
        }
	}

    private class GetStatusBarColorFromImageTask extends AsyncTask<String, String, Bitmap>
    {
        @Override
        protected void onPostExecute(final Bitmap bitmap)
        {
            if(bitmap == null)
            {
                if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
                {
                    getWindow().setStatusBarColor(ContextCompat.getColor(mContext, R.color.green));

                    mStatusBarCoverArtColor = R.color.black;
                }

                mWebView.loadUrl("javascript:setCoverArtFabColor('"+ContextCompat.getColor(mContext, R.color.green)+"')");
            }
            else
            {
                Palette.from(bitmap).generate(new Palette.PaletteAsyncListener()
                {
                    public void onGenerated(Palette palette)
                    {
                        final int vibrantColor = palette.getVibrantColor(ContextCompat.getColor(mContext, R.color.black));

                        final String vibrantColorHex = String.format("#%06X", (0xFFFFFF & vibrantColor));

                        final float[] colorHsv = new float[3];

                        Color.colorToHSV(vibrantColor, colorHsv);
                        colorHsv[2] *= 0.8f;

                        final int darkVibrantColor = Color.HSVToColor(colorHsv);

                        runOnUiThread(new Runnable()
                        {
                            @Override
                            public void run()
                            {
                                if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
                                {
                                    getWindow().setStatusBarColor(darkVibrantColor);

                                    mStatusBarCoverArtColor = darkVibrantColor;
                                }

                                mWebView.loadUrl("javascript:setCoverArtFabColor('"+vibrantColorHex+"')");
                            }
                        });
                    }
                });
            }
        }

        @Override
        protected Bitmap doInBackground(String... strings)
        {
            Bitmap bitmap = null;

            try
            {
                URL uri = new URL(strings[0]);

                HttpURLConnection httpURLConnection = (HttpURLConnection) uri.openConnection();

                httpURLConnection.setDoInput(true);
                httpURLConnection.setConnectTimeout(2500);
                httpURLConnection.setReadTimeout(10000);
                httpURLConnection.connect();

                InputStream inputStream = httpURLConnection.getInputStream();

                bitmap = BitmapFactory.decodeStream(inputStream);
            }
            catch(Exception e)
            {
                Log.e("WebViewActivity", Log.getStackTraceString(e));
            }

            return bitmap;
        }
    }
}