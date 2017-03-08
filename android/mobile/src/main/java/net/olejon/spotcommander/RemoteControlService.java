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

import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.hardware.Sensor;
import android.hardware.SensorEvent;
import android.hardware.SensorEventListener;
import android.hardware.SensorManager;
import android.os.Handler;
import android.os.IBinder;
import android.telephony.PhoneStateListener;
import android.telephony.TelephonyManager;

public class RemoteControlService extends Service implements SensorEventListener
{
	private final MyTools mTools = new MyTools(this);

	private PhoneStateListener mPhoneStateListener;
	private TelephonyManager mTelephonyManager;
	private SensorManager mSensorManager;
	private Sensor mSensor;

	private String mCurrentNetwork;

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
        mPhoneStateListener = new PhoneStateListener()
		{
		    @Override
		    public void onCallStateChanged(int state, String incomingNumber)
		    {
                mPauseOnIncomingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_INCOMING_CALL");
                mPauseOnOutgoingCall = mTools.getSharedPreferencesBoolean("PAUSE_ON_OUTGOING_CALL");

		    	final long computerId = mTools.getSharedPreferencesLong("LAST_COMPUTER_ID");

		    	if(state == TelephonyManager.CALL_STATE_RINGING)
		    	{	
		    		if(mPauseOnIncomingCall && mCurrentNetwork.equals(mTools.getCurrentNetwork())) mTools.remoteControl(computerId, "pause", "");

                    mIsIncomingCall = true;
		    	}
		    	else if(state == TelephonyManager.CALL_STATE_OFFHOOK && !mIsIncomingCall)
		    	{
		    		if(mPauseOnOutgoingCall && mCurrentNetwork.equals(mTools.getCurrentNetwork())) mTools.remoteControl(computerId, "pause", "");
		    	}
		    	else if(state == TelephonyManager.CALL_STATE_IDLE)
		    	{
                    mIsIncomingCall = false;
		    	}

		        super.onCallStateChanged(state, incomingNumber);
		    }
		};

        mTelephonyManager = (TelephonyManager) getSystemService(TELEPHONY_SERVICE);

        mSensorManager = (SensorManager) getSystemService(Context.SENSOR_SERVICE);
        mSensor = mSensorManager.getDefaultSensor(Sensor.TYPE_ACCELEROMETER);

		if(mSensor != null) mDeviceHasAccelerometer = true;
	}

	// Start service
	@Override
	public int onStartCommand(Intent intent, int flags, int startId)
	{
        mCurrentNetwork = mTools.getCurrentNetwork();

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
	public IBinder onBind(Intent arg0)
	{
		return null;
	}

	// Destroy service
	@Override
	public void onDestroy()
	{
        super.onDestroy();

        mTelephonyManager.listen(mPhoneStateListener, PhoneStateListener.LISTEN_NONE);

		if(mDeviceHasAccelerometer) mSensorManager.unregisterListener(this);
	}

	// Accelerometer
	@Override
	public final void onAccuracyChanged(Sensor sensor, int accuracy) { }

	@Override
	public final void onSensorChanged(SensorEvent event)
	{
		final float x = event.values[0];
		final float y = event.values[1];
		final float z = event.values[2];

		if(mFlipToPause)
		{
			if(z < -9.5)
			{
				if(!mIsFlipped && mCurrentNetwork.equals(mTools.getCurrentNetwork()))
				{
					final Runnable isFlippedRunnable= new Runnable()
					{
						public void run()
						{
							if(mIsFlipped) mTools.remoteControl(mTools.getSharedPreferencesLong("LAST_COMPUTER_ID"), "pause", "");
						}			
					};

					final Handler isFlippedHandler = new Handler();

					isFlippedHandler.removeCallbacks(isFlippedRunnable);
					isFlippedHandler.postDelayed(isFlippedRunnable, 1000);
				}

                mIsFlipped = true;
			}
			else if(z > -7)
			{
                mIsFlipped = false;
			}
		}

		if(mShakeToSkip)
		{
            mShakeToSkipLast = mShakeToSkipCurrent;
            mShakeToSkipCurrent = (float) Math.sqrt((double) (x * x + y * y + z * z));

			final float shakeToSkipDelta = mShakeToSkipCurrent - mShakeToSkipLast;

            mShakeToSkipChange = mShakeToSkipChange * 0.9f + shakeToSkipDelta;

			if(mShakeToSkipChange > mShakeToSkipSensitivityInt)
			{
				if(!mIsShaked && mCurrentNetwork.equals(mTools.getCurrentNetwork()))
				{
                    mIsShaked = true;

                    mTools.showToast("Shake detected, playing next track", 0);

                    mTools.remoteControl(mTools.getSharedPreferencesLong("LAST_COMPUTER_ID"), "next", "");
				}

				final Runnable isShakedRunnable= new Runnable()
				{
					public void run()
					{
                        mIsShaked = false;
					}			
				};

				final Handler isShakedHandler = new Handler();

				isShakedHandler.removeCallbacks(isShakedRunnable);
				isShakedHandler.postDelayed(isShakedRunnable, 500);
			}
		}
	}
}