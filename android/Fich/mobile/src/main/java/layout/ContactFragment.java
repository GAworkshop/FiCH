package layout;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.os.Bundle;
import android.provider.ContactsContract;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import com.example.user.fich.Contact;
import com.example.user.fich.PreferencesHelper;
import com.example.user.fich.R;

import java.util.ArrayList;
import java.util.HashSet;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link ContactFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link ContactFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class ContactFragment extends Fragment {

    PreferencesHelper prefHelper;
    ArrayList<Contact> contact_list = new ArrayList<Contact>();
    ListView contactListView;
    static final int PICK_CONTACT_REQUEST = 100;
    ContactAdapter contactAdapter;

    public ContactFragment() {
        // Required empty public constructor
    }

    public static ContactFragment newInstance(String param1, String param2) {
        Log.e("~~DEBUG~~", "Contact Fragment newInstance");
        ContactFragment fragment = new ContactFragment();
        Bundle args = new Bundle();
//        args.putString(ARG_PARAM1, param1);
//        args.putString(ARG_PARAM2, param2);
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.e("~~DEBUG~~", "Contact Fragment onCreate");
        prefHelper = new PreferencesHelper(getActivity());
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        Log.e("~~DEBUG~~", "Contact Fragment onCreateView");

        contact_list = prefHelper.getContactList();

        final View view = inflater.inflate(R.layout.fragment_contact, container, false);
        contactListView = (ListView)view.findViewById(R.id.contactListView);
        contactAdapter = new ContactAdapter(getActivity());
        contactListView.setAdapter(contactAdapter);

        contactListView.setOnItemLongClickListener(new AdapterView.OnItemLongClickListener() {
            @Override
            public boolean onItemLongClick(AdapterView<?> parent, View view, final int position, long id) {
                new AlertDialog.Builder(getActivity())
                        .setTitle("刪除聯絡人")
                        .setMessage("確定要刪除聯絡人 " + contact_list.get(position).getName() + " 嗎")
                        .setPositiveButton("確認", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {
                                contact_list.remove(position);
                                contactAdapter.notifyDataSetChanged();
                                resaveContactList();
                            }
                        })
                        .setNegativeButton("取消", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int which) {

                            }
                        })
                        .show();

                return false;
            }
        });

        Button contactAddBtn = (Button)view.findViewById(R.id.contactAddBtn);
        contactAddBtn.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                if(contact_list.size()<3) {
                    Intent pickContactIntent = new Intent(Intent.ACTION_PICK, Uri.parse("content://contacts"));
                    pickContactIntent.setType(ContactsContract.CommonDataKinds.Phone.CONTENT_TYPE);
                    startActivityForResult(pickContactIntent, PICK_CONTACT_REQUEST);
                }else{
                    new AlertDialog.Builder(getActivity())
                            .setTitle("很抱歉!")
                            .setMessage("聯絡人數量已達上限")
                            .setPositiveButton("確認", new DialogInterface.OnClickListener() {
                                @Override
                                public void onClick(DialogInterface dialog, int which) {

                                }
                            })
                            .show();
                }
            }
        });

        return view;
    }

    @Override
    public void onActivityResult(int requestCode, int resultCode, Intent data) {
        // Check which request it is that we're responding to
        if (requestCode == PICK_CONTACT_REQUEST) {
            // Make sure the request was successful
            if (resultCode == Activity.RESULT_OK) {
                // Get the URI that points to the selected contact
                Uri contactUri = data.getData();
                // We only need the NUMBER column, because there will be only one row in the result
                String[] projection = {ContactsContract.CommonDataKinds.Phone.NUMBER, ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME};

                Cursor cursor = getActivity().getContentResolver()
                        .query(contactUri, projection, null, null, null);
                cursor.moveToFirst();

                // Retrieve the phone number from the NUMBER column
                int column = cursor.getColumnIndex(ContactsContract.CommonDataKinds.Phone.NUMBER);
                String phone = cursor.getString(column);
                column = cursor.getColumnIndex(ContactsContract.CommonDataKinds.Phone.DISPLAY_NAME);
                String name = cursor.getString(column);
                // Do something with the phone number...
                for(Contact c:contact_list){
                    if(phone.equals(c.getPhone())){
                        Toast.makeText(getActivity(), "聯絡人電話重複", Toast.LENGTH_SHORT).show();
                        return;
                    }
                }
                contact_list.add(new Contact(name, phone));
                contactAdapter.notifyDataSetChanged();
                Toast.makeText(getActivity(), "新增聯絡人成功", Toast.LENGTH_SHORT).show();
                resaveContactList();
            }
        }
    }

    public void resaveContactList(){
        HashSet<String> hs = new HashSet<>();
        for(Contact c:contact_list){
            hs.add(c.getName()+","+c.getPhone());
        }
        prefHelper.storeData("contactList",hs);
    }

    @Override
    public void onAttach(Context context) {
        super.onAttach(context);
        Log.e("~~DEBUG~~", "Contact Fragment onAttach");
        /*
        if (context instanceof OnFragmentInteractionListener) {
            mListener = (OnFragmentInteractionListener) context;
        } else {
            throw new RuntimeException(context.toString()
                    + " must implement OnFragmentInteractionListener");
        }
        */
    }

    @Override
    public void onDetach() {
        super.onDetach();
        Log.e("~~DEBUG~~", "Contact Fragment onDEtach");
        //mListener = null;
    }

    @Override
    public void onDestroy(){
        super.onDestroy();
        Log.e("~~DEBUG~~", "Contact Fragment onDestroy");
    }


    /**
     * This interface must be implemented by activities that contain this
     * fragment to allow an interaction in this fragment to be communicated
     * to the activity and potentially other fragments contained in that
     * activity.
     * <p/>
     * See the Android Training lesson <a href=
     * "http://developer.android.com/training/basics/fragments/communicating.html"
     * >Communicating with Other Fragments</a> for more information.
     */
    public interface OnFragmentInteractionListener {
        // TODO: Update argument type and name
        void onFragmentInteraction(Uri uri);
    }

    private class ContactAdapter extends BaseAdapter{
        private LayoutInflater layoutInflater;

        public ContactAdapter(Context context){
            layoutInflater = LayoutInflater.from(context);
        }

        @Override
        public int getCount() {
            return contact_list.size();
        }

        @Override
        public Object getItem(int position) {
            return contact_list.get(position);
        }

        @Override
        public long getItemId(int position) {
            return contact_list.get(position).getId();
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.contactlistview_item, parent, false);
            }

            Contact contact = contact_list.get(position);
            TextView nameTv = (TextView)convertView.findViewById(R.id.contactNameTv);
            nameTv.setText(contact.getName());
            TextView phoneTv = (TextView)convertView.findViewById(R.id.contactPhoneTv);
            phoneTv.setText(contact.getPhone());

            return convertView;
        }
    }

}