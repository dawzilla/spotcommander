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

import android.app.PendingIntent;
import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.IntentSender;
import android.content.ServiceConnection;
import android.content.pm.ActivityInfo;
import android.os.Bundle;
import android.os.IBinder;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.MenuItem;
import android.view.View;
import android.view.WindowManager;
import android.widget.Button;

import com.android.vending.billing.IInAppBillingService;

import org.json.JSONObject;

import java.util.ArrayList;

public class DonateActivity extends AppCompatActivity
{
	private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private IInAppBillingService mIInAppBillingService;

	// Create activity
	@Override
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

		// Transition
		overridePendingTransition(R.anim.donate_start, R.anim.none);

		// Layout
		setContentView(R.layout.activity_donate);

		// Toolbar
		Toolbar toolbar = findViewById(R.id.donate_toolbar);
		toolbar.setNavigationIcon(R.drawable.ic_close_white_24dp);

		setSupportActionBar(toolbar);
		if(getSupportActionBar() != null) getSupportActionBar().setDisplayHomeAsUpEnabled(true);

		// In-app billing
		Intent intent = new Intent("com.android.vending.billing.InAppBillingService.BIND");
		intent.setPackage("com.android.vending");

		bindService(intent, mServiceConnection, Context.BIND_AUTO_CREATE);
	}

	// Menu
	@Override
	public boolean onOptionsItemSelected(MenuItem item)
	{
		switch(item.getItemId())
		{
			case android.R.id.home:
			{
				finish();
				return true;
			}
			default:
			{
				return super.onOptionsItemSelected(item);
			}
		}
	}

	// Activity result
	@Override
	protected void onActivityResult(int requestCode, int resultCode, Intent data)
	{
		if(requestCode == 1)
		{
			String purchaseData = data.getStringExtra("INAPP_PURCHASE_DATA");

			if(resultCode == RESULT_OK)
			{
				try
				{
					JSONObject purchaseDataJsonObject = new JSONObject(purchaseData);

					consumeDonation(purchaseDataJsonObject.getString("purchaseToken"));

					mTools.showToast(getString(R.string.donate_thank_you), 1);

					finish();
				}
				catch(Exception e)
				{
					mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

					Log.e("DonateActivity", Log.getStackTraceString(e));
				}
			}
		}
	}

	// Destroy activity
	@Override
	protected void onDestroy()
	{
		super.onDestroy();

		unbindService(mServiceConnection);
	}

	// Back button
	@Override
	public void onBackPressed()
	{
		super.onBackPressed();

		overridePendingTransition(0, R.anim.donate_finish);
	}

	// Donate
	private void makeDonation(String product)
	{
		try
		{
			Bundle bundle = mIInAppBillingService.getBuyIntent(3, getPackageName(), product, "inapp", "");

			PendingIntent pendingIntent = bundle.getParcelable("BUY_INTENT");

			IntentSender intentSender = (pendingIntent != null) ? pendingIntent.getIntentSender() : null;

			startIntentSenderForResult(intentSender, 1, new Intent(), 0, 0, 0);
		}
		catch(Exception e)
		{
			mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

			Log.e("DonateActivity", Log.getStackTraceString(e));
		}
	}

	private void consumeDonation(String purchaseToken)
	{
		try
		{
			mIInAppBillingService.consumePurchase(3, getPackageName(), purchaseToken);
		}
		catch(Exception e)
		{
			mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

			Log.e("DonateActivity", Log.getStackTraceString(e));
		}
	}

	// Service
	private final ServiceConnection mServiceConnection = new ServiceConnection()
	{
		@Override
		public void onServiceDisconnected(ComponentName name)
		{
			mIInAppBillingService = null;
		}

		@Override
		public void onServiceConnected(ComponentName name, IBinder service)
		{
			mIInAppBillingService = IInAppBillingService.Stub.asInterface(service);

			ArrayList<String> skusArrayList = new ArrayList<>();

			skusArrayList.add("small_donation");
			skusArrayList.add("medium_donation");
			skusArrayList.add("big_donation");

			Bundle productIdBundle = new Bundle();
			productIdBundle.putStringArrayList("ITEM_ID_LIST", skusArrayList);

			Bundle skuDetailsBundle = null;

			try
			{
				skuDetailsBundle = mIInAppBillingService.getSkuDetails(3, getPackageName(), "inapp", productIdBundle);
			}
			catch(Exception e)
			{
				Log.e("DonateActivity", Log.getStackTraceString(e));
			}

			if(skuDetailsBundle == null)
			{
				mTools.showToast(getString(R.string.donate_something_went_wrong), 1);
			}
			else
			{
				try
				{
					int responseCode = skuDetailsBundle.getInt("RESPONSE_CODE");

					if(responseCode == 0)
					{
						Button makeSmallDonationButton = findViewById(R.id.donate_make_small_donation);
						Button makeMediumDonationButton = findViewById(R.id.donate_make_medium_donation);
						Button makeBigDonationButton = findViewById(R.id.donate_make_big_donation);

						makeSmallDonationButton.setOnClickListener(new View.OnClickListener()
						{
							@Override
							public void onClick(View view)
							{
								makeDonation("small_donation");
							}
						});

						makeMediumDonationButton.setOnClickListener(new View.OnClickListener()
						{
							@Override
							public void onClick(View view)
							{
								makeDonation("medium_donation");
							}
						});

						makeBigDonationButton.setOnClickListener(new View.OnClickListener()
						{
							@Override
							public void onClick(View view)
							{
								makeDonation("big_donation");
							}
						});

						ArrayList<String> responseArrayList = skuDetailsBundle.getStringArrayList("DETAILS_LIST");

						if(responseArrayList != null)
						{
							for(String details : responseArrayList)
							{
								JSONObject detailsJsonObject = new JSONObject(details);

								String sku = detailsJsonObject.getString("productId");
								String price = detailsJsonObject.getString("price");

								switch(sku)
								{
									case "small_donation":
									{
										makeSmallDonationButton.setText(getString(R.string.donate_donate, price));
										break;
									}
									case "medium_donation":
									{
										makeMediumDonationButton.setText(getString(R.string.donate_donate, price));
										break;
									}
									case "big_donation":
									{
										makeBigDonationButton.setText(getString(R.string.donate_donate, price));
										break;
									}
								}
							}
						}
					}
				}
				catch(Exception e)
				{
					mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

					Log.e("DonateActivity", Log.getStackTraceString(e));
				}
			}
		}
	};
}