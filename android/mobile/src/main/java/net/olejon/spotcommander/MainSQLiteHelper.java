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
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;

final class MainSQLiteHelper extends SQLiteOpenHelper
{
	public static final String TABLE_COMPUTERS = "computers";

	public static final String COLUMN_ID = "_id";
	public static final String COLUMN_NAME = "name";
	public static final String COLUMN_URI = "uri";
	public static final String COLUMN_USERNAME = "username";
	public static final String COLUMN_PASSWORD = "password";
	public static final String COLUMN_NETWORK_NAME = "network_name";
	public static final String COLUMN_NETWORK_DEFAULT = "network_default";

	public MainSQLiteHelper(Context context)
	{
		super(context, "database.db", null, 9);
	}

	@Override
	public void onCreate(SQLiteDatabase db)
	{
		db.execSQL("CREATE TABLE "+TABLE_COMPUTERS+"("+COLUMN_ID+" INTEGER PRIMARY KEY AUTOINCREMENT, "+COLUMN_NAME+" TEXT, "+COLUMN_URI+" TEXT, "+COLUMN_USERNAME+" TEXT, "+COLUMN_PASSWORD+" TEXT, "+COLUMN_NETWORK_NAME+" TEXT, "+COLUMN_NETWORK_DEFAULT+" INTEGER);");
	}

	@Override
	public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion)
	{
	    db.execSQL("DROP TABLE IF EXISTS "+TABLE_COMPUTERS);

	    onCreate(db);
	}
}