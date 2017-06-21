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

import android.content.Context;
import android.os.Bundle;
import android.support.annotation.NonNull;
import android.support.v4.content.ContextCompat;
import android.support.wearable.activity.WearableActivity;
import android.view.View;
import android.widget.LinearLayout;

import com.google.android.gms.common.api.GoogleApiClient;
import com.google.android.gms.common.api.ResultCallback;
import com.google.android.gms.wearable.Node;
import com.google.android.gms.wearable.NodeApi;
import com.google.android.gms.wearable.Wearable;

public class MainActivity extends WearableActivity
{
    private static final String MESSAGE_PATH = "/remote_control";

    private final Context mContext = this;

    private LinearLayout linearLayout;

    private GoogleApiClient mGoogleApiClient;

    // Create activity
    @Override
    protected void onCreate(Bundle savedInstanceState)
    {
        super.onCreate(savedInstanceState);

        // Layout
        setContentView(R.layout.activity_main);

        linearLayout = (LinearLayout) findViewById(R.id.wear_main_layout);

        // Ambient mode
        setAmbientEnabled();

        // Google API client
        mGoogleApiClient = new GoogleApiClient.Builder(mContext).addApiIfAvailable(Wearable.API).build();
    }

    // Resume activity
    @Override
    protected void onResume()
    {
        super.onResume();

        mGoogleApiClient.connect();
    }

    // Ambient mode
    @Override
    public void onEnterAmbient(Bundle ambientDetails)
    {
        super.onEnterAmbient(ambientDetails);

        linearLayout.setBackgroundColor(ContextCompat.getColor(mContext, R.color.black));
    }

    @Override
    public void onExitAmbient()
    {
        super.onExitAmbient();

        linearLayout.setBackgroundResource(R.drawable.background);
    }

    // Buttons
    public void onButtonClick(View view)
    {
        switch(view.getId())
        {
            case R.id.wear_adjust_volume_mute_button:
            {
                sendMessage("adjust_volume_mute");
                break;
            }
            case R.id.wear_adjust_volume_down_button:
            {
                sendMessage("adjust_volume_down");
                break;
            }
            case R.id.wear_adjust_volume_up_button:
            {
                sendMessage("adjust_volume_up");
                break;
            }
            case R.id.wear_previous_button:
            {
                sendMessage("previous");
                break;
            }
            case R.id.wear_play_button:
            {
                sendMessage("play_pause");
                break;
            }
            case R.id.wear_next_button:
            {
                sendMessage("next");
                break;
            }
            case R.id.wear_seek_back_button:
            {
                sendMessage("seek_back");
                break;
            }
            case R.id.wear_launch_quit_button:
            {
                sendMessage("launch_quit");
                break;
            }
            case R.id.wear_seek_forward_button:
            {
                sendMessage("seek_forward");
                break;
            }
        }
    }

    // Send message
    private void sendMessage(final String message)
    {
        if(mGoogleApiClient != null && mGoogleApiClient.isConnected())
        {
            Wearable.NodeApi.getConnectedNodes(mGoogleApiClient).setResultCallback(new ResultCallback<NodeApi.GetConnectedNodesResult>()
            {
                @Override
                public void onResult(@NonNull NodeApi.GetConnectedNodesResult getConnectedNodesResult)
                {
                    String nodeId = null;

                    for(Node node : getConnectedNodesResult.getNodes())
                    {
                        if(node.isNearby())
                        {
                            nodeId = node.getId();

                            break;
                        }

                        nodeId = node.getId();
                    }

                    if(nodeId != null) Wearable.MessageApi.sendMessage(mGoogleApiClient, nodeId, MESSAGE_PATH, message.getBytes());
                }
            });
        }
    }
}