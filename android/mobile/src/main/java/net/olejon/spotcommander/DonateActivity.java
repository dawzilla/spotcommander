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
import android.os.AsyncTask;
import android.os.Bundle;
import android.os.IBinder;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.WindowManager;
import android.widget.Button;

import com.android.vending.billing.IInAppBillingService;

import org.json.JSONObject;

import java.util.ArrayList;

public class DonateActivity extends AppCompatActivity
{
    private final MyTools mTools = new MyTools(this);

    private IInAppBillingService mIInAppBillingService;

    // Create activity
    @Override
    protected void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);

        // Allow landscape?
        if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

        // Hide status bar?
        if(mTools.getDefaultSharedPreferencesBoolean("HIDE_STATUS_BAR")) getWindow().setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN, WindowManager.LayoutParams.FLAG_FULLSCREEN);

        // Transition
        overridePendingTransition(R.anim.donate_start, R.anim.none);

        // Layout
        setContentView(R.layout.activity_donate);

        // Toolbar
        final Toolbar toolbar = (Toolbar) findViewById(R.id.donate_toolbar);
        toolbar.setNavigationIcon(R.drawable.ic_close_white_24dp);

        setSupportActionBar(toolbar);
        if(getSupportActionBar() != null) getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        // In-app billing
        Intent intent = new Intent("com.android.vending.billing.InAppBillingService.BIND");
        intent.setPackage("com.android.vending");
        bindService(intent, mServiceConnection, Context.BIND_AUTO_CREATE);
    }

    // Activity result
    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data)
    {
        if(requestCode == 1)
        {
            final String purchaseData = data.getStringExtra("INAPP_PURCHASE_DATA");

            if(resultCode == RESULT_OK)
            {
                try
                {
                    final JSONObject purchaseDataJsonObject = new JSONObject(purchaseData);
                    final String purchaseToken = purchaseDataJsonObject.getString("purchaseToken");

                    consumeDonation(purchaseToken);

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

    // Menu
    @Override
    public boolean onCreateOptionsMenu(Menu menu)
    {
        getMenuInflater().inflate(R.menu.menu_donate, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item)
    {
        switch(item.getItemId())
        {
            case android.R.id.home:
            {
                finish();
                overridePendingTransition(0, R.anim.donate_finish);
                return true;
            }
            case R.id.donate_menu_reset:
            {
                resetDonations();
                return true;
            }
            default:
            {
                return super.onOptionsItemSelected(item);
            }
        }
    }

    // Donate
    private void makeDonation(final String product)
    {
        try
        {
            final Bundle buyIntentBundle = mIInAppBillingService.getBuyIntent(3, getPackageName(), product, "inapp", "");

            final PendingIntent pendingIntent = buyIntentBundle.getParcelable("BUY_INTENT");

            final IntentSender intentSender = (pendingIntent != null) ? pendingIntent.getIntentSender() : null;

            startIntentSenderForResult(intentSender, 1, new Intent(), 0, 0, 0);
        }
        catch(Exception e)
        {
            mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

            Log.e("DonateActivity", Log.getStackTraceString(e));
        }
    }

    private void consumeDonation(final String purchaseToken)
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

    private void resetDonations()
    {
        try
        {
            final Bundle purchasesBundle = mIInAppBillingService.getPurchases(3, getPackageName(), "inapp", null);

            final int responseCode = purchasesBundle.getInt("RESPONSE_CODE");

            if(responseCode == 0)
            {
                final ArrayList<String> purchaseDataList = purchasesBundle.getStringArrayList("INAPP_PURCHASE_DATA_LIST");

                if(purchaseDataList != null)
                {
                    for(String purchaseData : purchaseDataList)
                    {
                        consumeDonation(new JSONObject(purchaseData).getString("purchaseToken"));
                    }
                }

                mTools.showToast(getString(R.string.donate_reset_success), 0);
            }
            else
            {
                mTools.showToast(getString(R.string.donate_something_went_wrong), 1);
            }
        }
        catch(Exception e)
        {
            mTools.showToast(getString(R.string.donate_something_went_wrong), 1);

            Log.e("DonateActivity", Log.getStackTraceString(e));
        }
    }

    private class GetDonationsTask extends AsyncTask<Void, Void, Bundle>
    {
        @Override
        protected void onPostExecute(final Bundle donationsBundle)
        {
            if(donationsBundle == null)
            {
                mTools.showToast(getString(R.string.donate_something_went_wrong), 1);
            }
            else
            {
                try
                {
                    final int responseCode = donationsBundle.getInt("RESPONSE_CODE");

                    if(responseCode == 0)
                    {
                        final Button makeSmallDonationButton = (Button) findViewById(R.id.donate_make_small_donation);
                        final Button makeMediumDonationButton = (Button) findViewById(R.id.donate_make_medium_donation);
                        final Button makeBigDonationButton = (Button) findViewById(R.id.donate_make_big_donation);

                        final ArrayList<String> detailsArrayList = donationsBundle.getStringArrayList("DETAILS_LIST");

                        if(detailsArrayList != null)
                        {
                            for(String details : detailsArrayList)
                            {
                                JSONObject detailsJsonObject = new JSONObject(details);

                                String productId = detailsJsonObject.getString("productId");
                                String price = detailsJsonObject.getString("price");

                                switch(productId)
                                {
                                    case "small_donation":
                                    {
                                        makeSmallDonationButton.setText(getString(R.string.donate_donate, price));

                                        makeSmallDonationButton.setOnClickListener(new View.OnClickListener()
                                        {
                                            @Override
                                            public void onClick(View view)
                                            {
                                                makeDonation("small_donation");
                                            }
                                        });

                                        break;
                                    }
                                    case "medium_donation":
                                    {
                                        makeMediumDonationButton.setText(getString(R.string.donate_donate, price));

                                        makeMediumDonationButton.setOnClickListener(new View.OnClickListener()
                                        {
                                            @Override
                                            public void onClick(View view)
                                            {
                                                makeDonation("medium_donation");
                                            }
                                        });

                                        break;
                                    }
                                    case "big_donation":
                                    {
                                        makeBigDonationButton.setText(getString(R.string.donate_donate, price));

                                        makeBigDonationButton.setOnClickListener(new View.OnClickListener()
                                        {
                                            @Override
                                            public void onClick(View view)
                                            {
                                                makeDonation("big_donation");
                                            }
                                        });

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

        @Override
        protected Bundle doInBackground(Void... voids)
        {
            final ArrayList<String> productIdArrayList = new ArrayList<>();

            productIdArrayList.add("small_donation");
            productIdArrayList.add("medium_donation");
            productIdArrayList.add("big_donation");

            final Bundle productIdBundle = new Bundle();
            productIdBundle.putStringArrayList("ITEM_ID_LIST", productIdArrayList);

            Bundle productDetailsBundle;

            try
            {
                productDetailsBundle = mIInAppBillingService.getSkuDetails(3, getPackageName(), "inapp", productIdBundle);
            }
            catch(Exception e)
            {
                Log.e("DonateActivity", Log.getStackTraceString(e));

                productDetailsBundle = null;
            }

            return productDetailsBundle;
        }
    }

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

            GetDonationsTask getDonationsTask = new GetDonationsTask();
            getDonationsTask.execute();
        }
    };
}