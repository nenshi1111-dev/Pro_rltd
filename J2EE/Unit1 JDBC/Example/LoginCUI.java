package test;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.Scanner;

public class LoginCUI {

	public static void main(String[] args) {
		
		Scanner sc=new Scanner(System.in);
		System.out.println("Enter username : ");
		String username=sc.nextLine();
		System.out.println("Enter password : ");
		String password=sc.nextLine();
		try
		{
			Class.forName("oracle.jdbc.driver.OracleDriver");
			Connection cn=DriverManager.getConnection("jdbc:oracle:thin:@localhost:1521:XE","system","spkm");
			Statement st=cn.createStatement();
		
			ResultSet rs=st.executeQuery("SELECT * FROM student WHERE username='"+username+"' AND password='"+password+"' ");
			if(rs.next())
			{
				System.out.println("Welcome user "+username);
			}
			else
			{
				System.out.println("Invalid username or password");
			}
			rs.close();
			st.close();
			cn.close();
		}catch(Exception e)
		{
			e.printStackTrace();
		}
	}

}
