package application;

import javafx.application.Application;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.layout.GridPane;
import javafx.scene.paint.Color;
import javafx.stage.Stage;

public class LoginGUI extends Application{

	public void start(Stage s) {
		
		Label userlbl=new Label("Username : ");
		Label passlbl=new Label("Password :");
		Label msg=new Label("");
		
		TextField usertf =new TextField();
		usertf.setPromptText("eg. abc.xyz@gmail.com");
		
		TextField passtf=new TextField();
		
		Button loginbtn=new Button("Login");
		Button clearbtn=new Button("Clear");
		
		loginbtn.setOnAction(e->{
			String user=usertf.getText();
			String pass=passtf.getText();
			
			if(user.isEmpty())
			{
				msg.setText("Please enter username");
				msg.setTextFill(Color.RED);
			}
			else if(pass.isEmpty())
			{
				msg.setText("Please enter password");
				msg.setTextFill(Color.RED);
			}
			else
			{
				
				if(user.equals("spkm") && pass.equals("123"))
				{
					msg.setText("Login successfully done");
					msg.setTextFill(Color.GREEN);
				}else {
					msg.setText("Invalid username or password");
					msg.setTextFill(Color.RED);
				}
			}
			
		});
		
		GridPane pane=new GridPane();
		pane.setVgap(10);
		pane.setHgap(10);
		pane.setAlignment(Pos.CENTER);
		pane.add(userlbl, 0, 0);
		pane.add(usertf, 1, 0);
		pane.add(passlbl, 0, 1);
		pane.add(passtf, 1, 1);
		pane.add(loginbtn, 1, 2);
		//pane.add(clearbtn, 1, 2);
		pane.add(msg, 1, 3);
		Scene sc=new Scene(pane,300,200);
		s.setScene(sc);
		s.setTitle("Login Form");
		s.show();
	}
	public static void main(String[] args) {
		
		launch(args);
	}

}
