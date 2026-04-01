
from locust import HttpUser, task, between

class DummyLoadTester(HttpUser):
    wait_time = between(1, 3)
    host = "http://localhost:8000"

    @task
    def load_root(self):
        self.client.get("/")
    