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

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.graphics.BitmapFactory;
import android.hardware.Sensor;
import android.hardware.SensorEvent;
import android.hardware.SensorEventListener;
import android.hardware.SensorManager;
import android.os.Build;
import android.os.Handler;
import android.os.IBinder;
import android.telephony.PhoneStateListener;
import android.telephony.TelephonyManager;

public class RemoteControlService extends Service implements SensorEventListener
{
	private final static String NOTIFICATION_CHANNEL_REMOTE_CONTROL = "net.olejon.spotcommander.NOTIFICATION_CHANNEL_REMOTE_CONTROL";

	private final static int NOTIFICATION_REMOTE_CONTROL_ID = 2;

	private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private PhoneStateListener mPhoneStateListener;
	private TelephonyManager mTelephonyManager;
	private SensorManager mSensorManager;
	private Sensor mSensor;

	private boolean mPauseOnIncomingCall = false;
	private boolean mPauseOnOutgoingCall = false;
	private boolean mDeviceHasAccelerometer = false;
	private boolean mFlipToPause = false;
	private boolean mShakeToSkip = false;
	private boolean mIsIncomingCall = false;
	private boolean mIsFlipped = false;
	private boolean mIsShaked = false;

	private int mShakeToSkipSensitivityInt;

	private float mShakeToSkipChange;
	private float mShakeToSkipCurrent;
	private float mShakeToSkipLast;

	// Create service
	@Override
	public void onCreate()
	{
		// Telephony
		mTelephonyManager = (TelephonyManager) getSystemService(TELEPHONY_SERVICE);

		mPhoneStateListener = new PhoneStateListener()
		{
			@Override
			public void onCallStateChanged(int state, String incomingNumber)
			{
				mPauseOnIncomingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_INCOMING_CALL");
				mPauseOnOutgoingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_OUTGOING_CALL");

				long computerId = mTools.getSharedPreferencesLong("LAST_COMPUTER_ID");

				if(state == TelephonyManager.CALL_STATE_RINGING)
				{
					if(mPauseOnIncomingCall) mTools.remoteControl(computerId, "pause", "");

					mIsIncomingCall = true;
				}
				else if(state == TelephonyManager.CALL_STATE_OFFHOOK && !mIsIncomingCall)
				{
					if(mPauseOnOutgoingCall) mTools.remoteControl(computerId, "pause", "");
				}
				else if(state == TelephonyManager.CALL_STATE_IDLE)
				{
					mIsIncomingCall = false;
				}

				super.onCallStateChanged(state, incomingNumber);
			}
		};

		// Accelerometer
		mSensorManager = (SensorManager) getSystemService(Context.SENSOR_SERVICE);

		if(mSensorManager != null) mSensor = mSensorManager.getDefaultSensor(Sensor.TYPE_ACCELEROMETER);

		if(mSensor != null) mDeviceHasAccelerometer = true;

		// Notification
		long computerId = mTools.getSharedPreferencesLong("LAST_COMPUTER_ID");

		Intent launchActivityIntent = new Intent(mContext, MainActivity.class);
		launchActivityIntent.setAction("android.intent.action.MAIN");
		launchActivityIntent.addCategory("android.intent.category.LAUNCHER");
		PendingIntent launchActivityPendingIntent = PendingIntent.getActivity(mContext, 0, launchActivityIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		Intent previousIntent = new Intent(mContext, RemoteControlIntentService.class);
		previousIntent.setAction("previous");
		previousIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
		PendingIntent previousPendingIntent = PendingIntent.getService(mContext, 0, previousIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		Intent seekBackIntent = new Intent(mContext, RemoteControlIntentService.class);
		seekBackIntent.setAction("seek_back");
		seekBackIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
		PendingIntent seekBackPendingIntent = PendingIntent.getService(mContext, 0, seekBackIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		Intent playPauseIntent = new Intent(mContext, RemoteControlIntentService.class);
		playPauseIntent.setAction("play_pause");
		playPauseIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
		PendingIntent playPausePendingIntent = PendingIntent.getService(mContext, 0, playPauseIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		Intent seekForwardIntent = new Intent(mContext, RemoteControlIntentService.class);
		seekForwardIntent.setAction("seek_forward");
		seekForwardIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
		PendingIntent seekForwardPendingIntent = PendingIntent.getService(mContext, 0, seekForwardIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		Intent nextIntent = new Intent(mContext, RemoteControlIntentService.class);
		nextIntent.setAction("next");
		nextIntent.putExtra(RemoteControlIntentService.REMOTE_CONTROL_INTENT_SERVICE_EXTRA, computerId);
		PendingIntent nextPendingIntent = PendingIntent.getService(mContext, 0, nextIntent, PendingIntent.FLAG_CANCEL_CURRENT);

		NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

		if(notificationManager != null)
		{
			Notification.Builder notificationBuilder = new Notification.Builder(mContext);

			notificationBuilder.setWhen(0)
					.setOngoing(true)
					.setContentTitle(getString(R.string.notification_title))
					.setContentText(getString(R.string.notification_text))
					.setContentIntent(launchActivityPendingIntent)
					.setSmallIcon(R.drawable.ic_play_arrow_white_24dp)
					.setLargeIcon(BitmapFactory.decodeResource(getResources(), R.drawable.app_icon));

			if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP)
			{
				notificationBuilder.setColor(getResources().getColor(R.color.light_green))
						.setStyle(new Notification.MediaStyle().setShowActionsInCompactView(0, 2, 4))
						.addAction(R.drawable.ic_skip_previous_white_24dp, getString(R.string.notification_action_previous), previousPendingIntent)
						.addAction(R.drawable.ic_fast_rewind_white_24dp, getString(R.string.notification_action_seek_back), seekBackPendingIntent)
						.addAction(R.drawable.ic_play_arrow_white_24dp, getString(R.string.notification_action_play_pause), playPausePendingIntent)
						.addAction(R.drawable.ic_fast_forward_white_24dp, getString(R.string.notification_action_seek_forward), seekForwardPendingIntent)
						.addAction(R.drawable.ic_skip_next_white_24dp, getString(R.string.notification_action_next), nextPendingIntent);
			}
			else
			{
				notificationBuilder.addAction(R.drawable.ic_skip_previous_white_24dp, getString(R.string.notification_action_previous), previousPendingIntent)
						.addAction(R.drawable.ic_play_arrow_white_24dp, getString(R.string.notification_action_play_pause), playPausePendingIntent)
						.addAction(R.drawable.ic_skip_next_white_24dp, getString(R.string.notification_action_next), nextPendingIntent);
			}

			if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.O)
			{
				NotificationChannel notificationChannel = new NotificationChannel(NOTIFICATION_CHANNEL_REMOTE_CONTROL, getString(R.string.notification_remote_control_channel), NotificationManager.IMPORTANCE_LOW);
				notificationChannel.setLockscreenVisibility(Notification.VISIBILITY_PUBLIC);
				notificationChannel.setDescription(getString(R.string.notification_text));
				notificationChannel.setShowBadge(true);
				notificationManager.createNotificationChannel(notificationChannel);
				notificationBuilder.setChannelId(NOTIFICATION_CHANNEL_REMOTE_CONTROL);
			}

			startForeground(NOTIFICATION_REMOTE_CONTROL_ID, notificationBuilder.build());
		}
	}

	@Override
	public int onStartCommand(Intent intent, int flags, int startId)
	{
		// Telephony
		mPauseOnIncomingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_INCOMING_CALL");
		mPauseOnOutgoingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_OUTGOING_CALL");

		if(mPauseOnIncomingCall || mPauseOnOutgoingCall)
		{
			mTelephonyManager.listen(mPhoneStateListener, PhoneStateListener.LISTEN_CALL_STATE);
		}
		else
		{
			mTelephonyManager.listen(mPhoneStateListener, PhoneStateListener.LISTEN_NONE);
		}

		// Accelerometer
		if(mDeviceHasAccelerometer)
		{
			mFlipToPause = mTools.getSharedPreferencesBoolean("FLIP_TO_PAUSE");
			mShakeToSkip = mTools.getSharedPreferencesBoolean("SHAKE_TO_SKIP");

			if(mFlipToPause || mShakeToSkip)
			{
				mSensorManager.registerListener(this, mSensor, SensorManager.SENSOR_DELAY_NORMAL);

				if(mShakeToSkip)
				{
					mShakeToSkipSensitivityInt = 14;

					switch(mTools.getSharedPreferencesString("SHAKE_TO_SKIP_SENSITIVITY"))
					{
						case "higher":
						{
							mShakeToSkipSensitivityInt = 10;
						}
						case "high":
						{
							mShakeToSkipSensitivityInt = 12;
						}
						case "low":
						{
							mShakeToSkipSensitivityInt = 16;
						}
						case "lower":
						{
							mShakeToSkipSensitivityInt = 18;
						}
					}

					mShakeToSkipChange = 0.00f;
					mShakeToSkipCurrent = SensorManager.GRAVITY_EARTH;
					mShakeToSkipLast = SensorManager.GRAVITY_EARTH;
				}
			}
			else
			{
				mSensorManager.unregisterListener(this);
			}
		}

		return START_STICKY;
	}

	// RPC
	@Override
	public IBinder onBind(Intent intent)
	{
		return null;
	}

	// Destroy service
	@Override
	public void onDestroy()
	{
		super.onDestroy();

		// Telephony
		mTelephonyManager.listen(mPhoneStateListener, PhoneStateListener.LISTEN_NONE);

		// Accelerometer
		if(mDeviceHasAccelerometer) mSensorManager.unregisterListener(this);

		// Notification
		NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

		if(notificationManager != null) notificationManager.cancel(NOTIFICATION_REMOTE_CONTROL_ID);
	}

	// Accelerometer
	@Override
	public void onAccuracyChanged(Sensor sensor, int accuracy) { }

	@Override
	public void onSensorChanged(SensorEvent event)
	{
		float x = event.values[0];
		float y = event.values[1];
		float z = event.values[2];

		// Flip to pause
		if(mFlipToPause)
		{
			if(z < - 9.5)
			{
				if(!mIsFlipped)
				{
					Runnable isFlippedRunnable = new Runnable()
					{
						public void run()
						{
							if(mIsFlipped) mTools.remoteControl(mTools.getSharedPreferencesLong("LAST_COMPUTER_ID"), "pause", "");
						}
					};

					Handler isFlippedHandler = new Handler();

					isFlippedHandler.removeCallbacks(isFlippedRunnable);
					isFlippedHandler.postDelayed(isFlippedRunnable, 1000);
				}

				mIsFlipped = true;
			}
			else if(z > - 7)
			{
				mIsFlipped = false;
			}
		}

		// Shake to skip
		if(mShakeToSkip)
		{
			mShakeToSkipLast = mShakeToSkipCurrent;
			mShakeToSkipCurrent = (float) Math.sqrt((double) (x * x + y * y + z * z));

			float shakeToSkipDelta = mShakeToSkipCurrent - mShakeToSkipLast;

			mShakeToSkipChange = mShakeToSkipChange * 0.9f + shakeToSkipDelta;

			if(mShakeToSkipChange > mShakeToSkipSensitivityInt)
			{
				if(!mIsShaked)
				{
					mIsShaked = true;

					mTools.showToast("Shake detected, playing next track", 0);

					mTools.remoteControl(mTools.getSharedPreferencesLong("LAST_COMPUTER_ID"), "next", "");
				}

				Runnable isShakedRunnable = new Runnable()
				{
					public void run()
					{
						mIsShaked = false;
					}
				};

				Handler isShakedHandler = new Handler();

				isShakedHandler.removeCallbacks(isShakedRunnable);
				isShakedHandler.postDelayed(isShakedRunnable, 500);
			}
		}
	}
}