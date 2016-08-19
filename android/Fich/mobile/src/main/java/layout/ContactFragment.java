package layout;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.support.v4.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseExpandableListAdapter;
import android.widget.ExpandableListView;
import android.widget.ImageView;
import android.widget.TextView;

import com.example.user.fich.Contact;
import com.example.user.fich.R;

import java.io.InputStream;
import java.util.ArrayList;

/**
 * A simple {@link Fragment} subclass.
 * Activities that contain this fragment must implement the
 * {@link ContactFragment.OnFragmentInteractionListener} interface
 * to handle interaction events.
 * Use the {@link ContactFragment#newInstance} factory method to
 * create an instance of this fragment.
 */
public class ContactFragment extends Fragment {

    //private OnFragmentInteractionListener mListener;
    private ExpandableListView expListView;

    SharedPreferences pref;
    SharedPreferences.Editor prefEdit;
    private static final String contact = "contact";
    private static final int IMAGE_PICKER = 1;
    private int lastExp = -1;
    public View targetView;

    ArrayList<Contact> contact_list = new ArrayList<Contact>();

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
        contact_list.add(new Contact(0, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Ben", "123456789"));
        contact_list.add(new Contact(1, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Huang", "000000000"));
        contact_list.add(new Contact(2, BitmapFactory.decodeResource(getResources(), R.drawable.head_me),"Huang", "111111111"));

        pref = getActivity().getApplication().getSharedPreferences(contact, Context.MODE_PRIVATE);
        prefEdit = pref.edit();
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container,
                             Bundle savedInstanceState) {
        // Inflate the layout for this fragment
        Log.e("~~DEBUG~~", "Contact Fragment onCreateView");



        View view = inflater.inflate(R.layout.fragment_contact, container, false);
        expListView = (ExpandableListView) view.findViewById(R.id.expandableListView);



        expListView.setAdapter(new ExpListAdapter(targetView));
        expListView.setGroupIndicator(null);


        expListView.setOnGroupExpandListener(new ExpandableListView.OnGroupExpandListener() {

            @Override
            public void onGroupExpand(int groupPosition) {
                Log.e("~~DEBUG~~", "onGroupExpand~~");
                if(groupPosition != lastExp){
                    expListView.collapseGroup(lastExp);
                }
                lastExp = groupPosition;
                Log.e("~~DEBUG~~", "lastExp: " + lastExp);
            }
        });

        expListView.setOnChildClickListener(new ExpandableListView.OnChildClickListener() {
            @Override
            public boolean onChildClick(ExpandableListView parent, View v, int groupPosition, int childPosition, long id) {
                Log.e("C", "CHILD");
                return false;
            }
        });

        return view;
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

    public class ExpListAdapter extends BaseExpandableListAdapter {

        private final LayoutInflater layoutInflater;


        public ExpListAdapter(View target) {
            layoutInflater = LayoutInflater.from(getActivity());
            targetView = target;
        }

        @Override
        public int getGroupCount() {
            //Log.e("~~DEBUG~~", "contact list size: "+contact_list.size());
            return contact_list.size();
        }

        @Override
        public int getChildrenCount(int groupPosition) {
            return 1;
        }

        @Override
        public Object getGroup(int groupPosition) {
            return contact_list.get(groupPosition);
        }

        @Override
        public Object getChild(int groupPosition, int childPosition) {
            return "CHILD";
        }

        @Override
        public long getGroupId(int groupPosition) {
            return groupPosition;
        }

        @Override
        public long getChildId(int groupPosition, int childPosition) {
            return childPosition;
        }

        @Override
        public boolean hasStableIds() {
            return true;
        }

        @Override
        public View getGroupView(int groupPosition, boolean isExpanded, View convertView, ViewGroup parent) {

            //Log.e("~~DEBUG~~", "getGroupView");
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.list_item_contact, parent, false);

            } else {

            }
            Contact c = contact_list.get(groupPosition);
            ((ImageView)convertView.findViewById(R.id.imageView)).setImageBitmap(c.getImage());
            ((TextView)convertView.findViewById(R.id.textView)).setText(c.getName());
            ((TextView)convertView.findViewById(R.id.textView9)).setText(c.getPhone());
            ((ImageView)convertView.findViewById(R.id.imageView2)).setImageResource(R.drawable.q);

            return convertView;
        }

        @Override
        public View getChildView(int groupPosition, int childPosition, boolean isLastChild, View convertView, ViewGroup parent) {
            //Log.e("~~DEBUG~~", "getChildView");
            if (convertView == null) {
                convertView = layoutInflater.inflate(R.layout.list_expanded_contact, parent, false);

            } else {
                //Log.e("~~DEBUG~~", "getChildView -- else");
            }
            ((TextView)convertView.findViewById(R.id.tv_phone)).setText("GGGGGGGGGGGG");
            convertView.findViewById(R.id.img_photo).setOnClickListener(new clickListener(convertView));
            targetView = convertView;

//            if(photos.get(groupPosition) != null){
//                try {
//                    InputStream in = getActivity().getContentResolver().openInputStream(photos.get(groupPosition));
//                    Bitmap img = BitmapFactory.decodeStream(in);
//                    in.close();
//                    ((ImageView)convertView.findViewById(R.id.imageView)).setImageBitmap(img);
//                }catch (Exception e){
//                    Log.e("~~DEBUGG~~", "data: " + photos.get(groupPosition));
//                }
//
//            }

            return convertView;
        }

        @Override
        public boolean isChildSelectable(int groupPosition, int childPosition) {
            return true;
        }

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

    private class clickListener implements View.OnClickListener{

        private View targetView;

        public clickListener(View targetView){
            this.targetView = targetView;
        }

        @Override
        public void onClick(View v) {
            /*
            Intent intent = new Intent();
            intent.setType("image/*");
            intent.setAction(Intent.ACTION_GET_CONTENT);
            startActivityForResult(intent, IMAGE_PICKER);
            */

            Intent intent = new Intent(Intent.ACTION_PICK, MediaStore.Images.Media.EXTERNAL_CONTENT_URI);
            intent.setAction(Intent.ACTION_GET_CONTENT);
            startActivityForResult(intent, IMAGE_PICKER);
        }
    }

    @Override
    public void onActivityResult(int requestCode, int resultCode, Intent data){
        Log.e("~~DEBUG~~", "onActivityResult");
        if(requestCode == IMAGE_PICKER && resultCode == Activity.RESULT_OK){
            Log.e("~~DEBUG~~", "request ok");
            try {
/*
                Uri uri = data.getData();
                String[] columns = {MediaStore.Images.Media.DATA};
                Cursor cursor = getActivity().getContentResolver().query(uri, columns, null, null, null);
                Log.e("~~DEBUG~~", "uri: "+uri.toString());
                Log.e("~~DEBUG~~", ""+columns[0].toString());
                if (cursor.moveToFirst()) {
                    String imagePath = cursor.getString(0);
                    Log.e("~~DEBUG~~", "imagePath: "+imagePath);
                    cursor.close();
                    Bitmap bitmap = BitmapFactory.decodeFile(imagePath);
                    //((View)expListView.getItemAtPosition(expListView.getPackedPositionGroup(expListView.getSelectedPosition()))).getChildAt(0);
                    if(expListView.isGroupExpanded(lastExp)){
                        ((ImageView)expListView.getFocusedChild().findViewById(R.id.imageView)).setImageBitmap(bitmap);
                    }
                    //imageView.setImageBitmap(bitmap);
                }
*/

                InputStream in = getActivity().getContentResolver().openInputStream(data.getData());
                Bitmap img = BitmapFactory.decodeStream(in);
                in.close();
                if(expListView.isGroupExpanded(lastExp)){
                    Log.e("~~DEBUG~~", "isGroupExpanded : true");
                    if(targetView != null){
                        Log.e("~~DEBUG~~", "targetView != null");
                        ((ImageView)targetView.findViewById(R.id.img_photo)).setImageBitmap(img);
                        contact_list.get(lastExp).setImage(img);
                        //((ImageView)expListView.getChildAt(expListView.getPackedPositionChild(expListView.getPackedPositionForChild(lastExp, 0))).findViewById(R.id.img_photo)).setImageBitmap(img);
                    }
                }





            }catch (Exception e){
                Log.e("~~DEBUG~~", "error: " + e.toString());
            }
        }
    }

}
