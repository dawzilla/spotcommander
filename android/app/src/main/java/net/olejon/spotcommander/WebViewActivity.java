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
import android.content.pm.ActivityInfo;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.net.http.SslError;
import android.os.Build;
import android.os.Bundle;
import android.os.PowerManager;
import android.support.annotation.NonNull;
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

import org.json.JSONArray;

import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Arrays;

public class WebViewActivity extends AppCompatActivity
{
	private final Activity mActivity = this;

	private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private PowerManager.WakeLock mWakeLock;

	private WebView mWebView;

	private String mProjectVersionName;
	private String mCurrentNetwork;

	private boolean mHasLongPressedBack = false;

	private int mStatusBarPrimaryColor = 0;
	private int mStatusBarCoverArtColor = 0;

	private Bitmap mBitmap;

	// Create activity
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

		// Connected?
		if(!mTools.isDeviceConnected())
		{
			mTools.showToast(getString(R.string.device_not_connected), 1);

			finish();

			return;
		}

		// Allow landscape?
		if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

		// Hide status bar?
		if(mTools.getDefaultSharedPreferencesBoolean("HIDE_STATUS_BAR")) getWindow().setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN, WindowManager.LayoutParams.FLAG_FULLSCREEN);

		// Power manager
		PowerManager powerManager = (PowerManager) getSystemService(Context.POWER_SERVICE);

		if(powerManager != null) mWakeLock = powerManager.newWakeLock(PowerManager.SCREEN_DIM_WAKE_LOCK, "mWakeLock");

		// Settings
		mTools.setSharedPreferencesBoolean("CAN_CLOSE_COVER", false);

		// Current network
		mCurrentNetwork = mTools.getCurrentNetwork();

		// Computer
		long computerId = mTools.getSharedPreferencesLong("LAST_COMPUTER_ID");

		if(computerId == 0)
		{
			mTools.showToast(getString(R.string.webview_no_computer_added_error), 1);

			finish();

			return;
		}

		String[] computer = mTools.getComputer(computerId);

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

		// Web view
		mWebView = findViewById(R.id.webview_webview);

		mWebView.setBackgroundColor(ContextCompat.getColor(mContext, R.color.background));

		mWebView.setWebViewClient(new WebViewClient()
		{
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

		String userAgentAppend1 = (!username.equals("") && !password.equals("")) ? "AUTHENTICATION_ENABLED" : "";
		String userAgentAppend2 = (Build.VERSION.SDK_INT < Build.VERSION_CODES.KITKAT && !mTools.getDefaultSharedPreferencesBoolean("HARDWARE_ACCELERATED_ANIMATIONS")) ? "DISABLE_CSSTRANSITIONS DISABLE_CSSTRANSFORMS3D" : "";

		// Web settings
		WebSettings webSettings = mWebView.getSettings();
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

		if(mWakeLock.isHeld()) mWakeLock.release();

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

		if(mCurrentNetwork.equals(mTools.getCurrentNetwork()))
		{
			mWebView.resumeTimers();
			mWebView.loadUrl("javascript:nativeAppLoad(true)");
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

		stopService(new Intent(mContext, RemoteControlService.class));
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
	@SuppressWarnings("unused")
	private class JavaScriptInterface
	{
		@JavascriptInterface
		public void JSstartService()
		{
			Intent remoteControlServiceIntent = new Intent(mContext, RemoteControlService.class);

			if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.O)
			{
				startForegroundService(remoteControlServiceIntent);
			}
			else
			{
				startService(remoteControlServiceIntent);
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
				if(!mWakeLock.isHeld()) mWakeLock.acquire(3600000);
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

				Intent intent = new Intent(Intent.ACTION_SEARCH);
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
			Intent intent = new Intent();
			intent.setAction(Intent.ACTION_SEND);
			intent.putExtra(Intent.EXTRA_TEXT, uri);
			intent.setType("text/plain");

			Intent chooserIntent = Intent.createChooser(intent, title);
			startActivity(chooserIntent);
		}

		@JavascriptInterface
		public void JSmakeDonation()
		{
			Intent intent = new Intent(mContext, DonateActivity.class);
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
		public void JSsetStatusBarColorFromImage(final String uri)
		{
			Thread getImagethread = new Thread(new Runnable()
			{
				@Override
				public void run()
				{
					mBitmap = null;

					try
					{
						URL bitmapUri = new URL(uri);

						HttpURLConnection httpURLConnection = (HttpURLConnection) bitmapUri.openConnection();

						httpURLConnection.setDoInput(true);
						httpURLConnection.setConnectTimeout(2500);
						httpURLConnection.setReadTimeout(10000);
						httpURLConnection.connect();

						InputStream inputStream = httpURLConnection.getInputStream();

						mBitmap = BitmapFactory.decodeStream(inputStream);
					}
					catch(Exception e)
					{
						Log.e("WebViewActivity", Log.getStackTraceString(e));
					}

					runOnUiThread(new Runnable()
					{
						@Override
						public void run()
						{
							if(mBitmap == null)
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
								Palette.from(mBitmap).generate(new Palette.PaletteAsyncListener()
								{
									public void onGenerated(@NonNull Palette palette)
									{
										int vibrantColor = palette.getVibrantColor(ContextCompat.getColor(mContext, R.color.black));

										final String vibrantColorHex = String.format("#%06X", (0xFFFFFF & vibrantColor));

										float[] colorHsv = new float[3];

										Color.colorToHSV(vibrantColor, colorHsv);
										colorHsv[2] *= 0.8f;

										final int darkVibrantColor = Color.HSVToColor(colorHsv);

										if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
										{
											getWindow().setStatusBarColor(darkVibrantColor);

											mStatusBarCoverArtColor = darkVibrantColor;
										}

										mWebView.loadUrl("javascript:setCoverArtFabColor('"+vibrantColorHex+"')");
									}
								});
							}
						}
					});
				}
			});

			getImagethread.start();
		}
	}
}