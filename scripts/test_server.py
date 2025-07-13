#!/usr/bin/env python3
"""
Simple test server to simulate the IT and NX domain websites
for testing purposes when Docker is not available.
"""

import http.server
import socketserver
import os
import json
from urllib.parse import urlparse, parse_qs
import threading
import time

class ITDomainHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory="/workspace/dv_website/it-domain", **kwargs)
    
    def do_GET(self):
        if self.path == '/':
            self.path = '/index.html'
        
        # Serve static test page for IT domain
        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()
        
        html_content = """
        <!DOCTYPE html>
        <html>
        <head>
            <title>IT Domain - DV Project Management</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
                <div class="container">
                    <a class="navbar-brand" href="/">IT Domain - DV Management</a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="/">View Data</a>
                        <a class="nav-link" href="/add_project">Add Project</a>
                        <a class="nav-link" href="/add_task">Add Task</a>
                    </div>
                </div>
            </nav>
            
            <div class="container mt-4">
                <h2>IT Domain Test Page</h2>
                <div class="alert alert-info">
                    <strong>Test Status:</strong> IT Domain website is running on port 8080
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>IT Domain Fields (17 Total)</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">1. Index (Auto-generated)</li>
                                    <li class="list-group-item">2. Project</li>
                                    <li class="list-group-item">3. SPIP_IP</li>
                                    <li class="list-group-item">4. IP</li>
                                    <li class="list-group-item">5. IP Postfix</li>
                                    <li class="list-group-item">6. IP Subtype</li>
                                    <li class="list-group-item">7. Alternative Name</li>
                                    <li class="list-group-item">8. DV Engineer</li>
                                    <li class="list-group-item">9. Digital Designer</li>
                                    <li class="list-group-item">10. Business Unit</li>
                                    <li class="list-group-item">11. Analog Designer</li>
                                    <li class="list-group-item">12. SPIP URL</li>
                                    <li class="list-group-item">13. Wiki URL</li>
                                    <li class="list-group-item">14. Spec Version</li>
                                    <li class="list-group-item">15. Spec Path</li>
                                    <li class="list-group-item">16. Inherit from IP</li>
                                    <li class="list-group-item">17. Re-use IP</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Test Features</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Task Index Auto-generation:</label>
                                    <input type="text" class="form-control" value="TASK_001" readonly>
                                    <small class="text-muted">Auto-generated task index</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label>URL Validation:</label>
                                    <input type="url" class="form-control" placeholder="https://example.com">
                                    <small class="text-muted">URL format validation</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label>IP Subtype (Constrained):</label>
                                    <select class="form-control">
                                        <option value="default">default</option>
                                        <option value="gen2x1">gen2x1</option>
                                    </select>
                                </div>
                                
                                <button class="btn btn-primary">Add Test Project</button>
                                <button class="btn btn-success">Export to CSV</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        """
        self.wfile.write(html_content.encode())

class NXDomainHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory="/workspace/dv_website/nx-domain", **kwargs)
    
    def do_GET(self):
        if self.path == '/':
            self.path = '/index.html'
        
        # Serve static test page for NX domain
        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()
        
        html_content = """
        <!DOCTYPE html>
        <html>
        <head>
            <title>NX Domain - DV Reports & TO Summary</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <nav class="navbar navbar-expand-lg navbar-dark bg-success">
                <div class="container">
                    <a class="navbar-brand" href="/">NX Domain - DV Reports</a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="/">Dashboard</a>
                        <a class="nav-link" href="/coverage">Coverage Reports</a>
                        <a class="nav-link" href="/import">Import IT Data</a>
                        <a class="nav-link" href="/to_summary">TO Summary</a>
                    </div>
                </div>
            </nav>
            
            <div class="container mt-4">
                <h2>NX Domain Test Page</h2>
                <div class="alert alert-success">
                    <strong>Test Status:</strong> NX Domain website is running on port 8081
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>NX Domain Fields (16 Total)</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">1. Line Coverage <span class="badge bg-success">95%</span></li>
                                    <li class="list-group-item">2. FSM Coverage <span class="badge bg-warning">85%</span></li>
                                    <li class="list-group-item">3. Interface Toggle Coverage <span class="badge bg-success">92%</span></li>
                                    <li class="list-group-item">4. Toggle Coverage <span class="badge bg-success">88%</span></li>
                                    <li class="list-group-item">5. Coverage Report Path</li>
                                    <li class="list-group-item">6. Sanity SVN</li>
                                    <li class="list-group-item">7. Sanity SVN Version</li>
                                    <li class="list-group-item">8. Release SVN</li>
                                    <li class="list-group-item">9. Release SVN Version</li>
                                    <li class="list-group-item">10. Git Path</li>
                                    <li class="list-group-item">11. Git Version</li>
                                    <li class="list-group-item">12. Golden Checklist</li>
                                    <li class="list-group-item">13. Golden Checklist Version</li>
                                    <li class="list-group-item">14. TO Date</li>
                                    <li class="list-group-item">15. RTL Last Update</li>
                                    <li class="list-group-item">16. TO Report Creation</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>TO Summary (33 Total Fields)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Line Cov</th>
                                                <th>FSM Cov</th>
                                                <th>DV Engineer</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Test_Project_1</td>
                                                <td><span class="badge bg-success">95%</span></td>
                                                <td><span class="badge bg-warning">85%</span></td>
                                                <td>John.Doe</td>
                                            </tr>
                                            <tr>
                                                <td>Test_Project_2</td>
                                                <td><span class="badge bg-success">92%</span></td>
                                                <td><span class="badge bg-success">90%</span></td>
                                                <td>Jane.Smith</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <strong>Combined Data:</strong> 17 IT fields + 16 NX fields = 33 total fields
                                </div>
                                
                                <div class="mt-3">
                                    <input type="file" class="form-control" accept=".csv">
                                    <button class="btn btn-primary mt-2">Import CSV from IT Domain</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        """
        self.wfile.write(html_content.encode())

def start_server(port, handler_class):
    """Start a simple HTTP server on the specified port"""
    with socketserver.TCPServer(("", port), handler_class) as httpd:
        print(f"Server running on port {port}")
        httpd.serve_forever()

if __name__ == "__main__":
    # Start IT domain server on port 8080
    it_thread = threading.Thread(target=start_server, args=(8080, ITDomainHandler))
    it_thread.daemon = True
    it_thread.start()
    
    # Start NX domain server on port 8081
    nx_thread = threading.Thread(target=start_server, args=(8081, NXDomainHandler))
    nx_thread.daemon = True
    nx_thread.start()
    
    print("Starting test servers...")
    print("IT Domain: http://localhost:8080")
    print("NX Domain: http://localhost:8081")
    print("Press Ctrl+C to stop")
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\nShutting down servers...")