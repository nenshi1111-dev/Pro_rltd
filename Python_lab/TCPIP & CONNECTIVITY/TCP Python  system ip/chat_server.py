import socket

def start_chat_server(host='127.0.0.1', port=65432):
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.bind((host, port))
    server_socket.listen(1)

    print(f"Server listening on {host}:{port}")
    conn, addr = server_socket.accept()
    print(f"Connected by {addr}")

    while True:
        # Receive message from client
        client_msg = conn.recv(1024).decode()
        if not client_msg or client_msg.lower() == "exit":
            print("Client disconnected.")
            break
        print(f"Client: {client_msg}")

        # Send reply
        server_msg = input("You (Server): ")
        conn.sendall(server_msg.encode())

        if server_msg.lower() == "exit":
            print("Closing connection.")
            break

    conn.close()
    server_socket.close()

if __name__ == "__main__":
    start_chat_server()
