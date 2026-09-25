import socket

def start_chat_client(server_host='192.168.2.28', server_port=65432):
    client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    client_socket.connect((server_host, server_port))

    print(f"Connected to server {server_host}:{server_port}")
    print("Type 'exit' to quit.\n")

    while True:
        # Send message to server
        msg = input("You (Client): ")
        client_socket.sendall(msg.encode())

        if msg.lower() == "exit":
            print("Closing connection.")
            break

        # Receive reply from server
        reply = client_socket.recv(1024).decode()
        if not reply or reply.lower() == "exit":
            print("Server closed the connection.")
            break
        print(f"Server: {reply}")

    client_socket.close()

if __name__ == "__main__":
    start_chat_client()
