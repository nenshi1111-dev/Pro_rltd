package application;

import java.io.DataInputStream;
import java.sql.*;
public class StudentApp {
	static Connection cn;
	public static void main(String[] args) {
		
		try
		{
				Class.forName("com.mysql.jdbc.Driver");
				cn=DriverManager.getConnection("jdbc:mysql://localhost:3306/studentdb","root","root");
				while(true)
				{
					System.out.println("\t\t Student Management System");
					System.out.println("1. Insert record");
					System.out.println("2. Display record");
					System.out.println("3. Update record");
					System.out.println("4. Delete record");
					System.out.println("5. Exit");
					System.out.println("Enter your choice : ");
					DataInputStream ds=new DataInputStream(System.in);
					int choice=Integer.parseInt(ds.readLine());
					switch(choice)
					{
						case 1:
							System.out.println("Enter firstname : ");
							String firstname=ds.readLine();
							System.out.println("Enter lastname : ");
							String lastname=ds.readLine();
							System.out.println("Enter city : ");
							String city=ds.readLine();
							System.out.println("Enter dob : ");
							String dob=ds.readLine();
							int count=insertRecord(firstname, lastname,city,dob);
							System.out.println(count+" Record inserted...");
						break;
						case 2:	
							displayRecord();
							ds.readLine();
						break;
						case 3:
							System.out.println("Enter id : ");
							int id=Integer.parseInt(ds.readLine());
							System.out.println("Enter firstname : ");
							firstname=ds.readLine();
							System.out.println("Enter lastname : ");
							lastname=ds.readLine();
							System.out.println("Enter city : ");
							city=ds.readLine();
							System.out.println("Enter dob : ");
							dob=ds.readLine();
							count=updateRecord(id,firstname, lastname,city,dob);
							System.out.println(count+" Record updated...");
						break;
						case 4:
							System.out.println("Enter id : ");
							id=Integer.parseInt(ds.readLine());
							
							count=deleteRercord(id);
							System.out.println(count+" Record deleted...");
						break;
						case 5:
							System.exit(0);
						default:
							System.out.println("Invalid choice ");
					}
				}
		}catch(Exception e)
		{
			e.printStackTrace();
		}
	}
	private static int insertRecord(String firstname, String lastname, String city, String dob) throws SQLException
	{
		Statement st=cn.createStatement();
		int x=st.executeUpdate("INSERT INTO student (firstname,lastname,city,dob) VALUES('"+firstname+"','"+lastname+"','"+city+"','"+dob+"')");
		st.close();
		return x;
	}
	private static int updateRecord(int id,String firstname, String lastname, String city, String dob) throws SQLException
	{
		Statement st=cn.createStatement();
		int x=st.executeUpdate("UPDATE student SET firstname='"+firstname+"', lastname='"+lastname+"',city='"+city+"', dob='"+dob+"' WHERE id="+id);
		st.close();
		return x;
	}
	private static int deleteRercord(int id) throws SQLException
	{
		Statement st=cn.createStatement();
		int x=st.executeUpdate(" DELETE FROM student WHERE id="+id);
		st.close();
		return x;
	}
	private static void displayRecord() throws SQLException
	{
		Statement st=cn.createStatement();
		ResultSet rs=st.executeQuery("SELECT * FROM student");
		while(rs.next())
		{
			System.out.println(rs.getInt("id")+" "+rs.getString("firstname")+" "+rs.getString("lastname")+" "+rs.getString("city")+" "+rs.getString("dob"));
		}
		rs.close();
		st.close();
	}

}
