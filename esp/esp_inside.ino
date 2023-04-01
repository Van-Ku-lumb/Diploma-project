//UTC libraries
#include <NTPClient.h>
#include <WiFiUdp.h>

WiFiUDP udp;
NTPClient ntp(udp, "pool.ntp.org", 10800);

//communication libraries
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>

WiFiClient wifi;
HTTPClient http;

String ssid;
String password;
String server_ip;

//BME libraries
#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>

Adafruit_BME280 bme;

#include <EEPROM.h>

String prompt(String message="?:",int secs=60){
  int attempts=0;
  String response="unresponsive";
  Serial.print(message);
  while(!Serial.available()&&++attempts<=secs)delay(1000);
  if(Serial.available()){response=Serial.readString();}
  String result="";
  for(int i=0;i<response.length();i++)
  {
    if(response[i]>=32&&response[i]<127)result+=response[i];
  }
  Serial.println(result);
  return result;
}
void WriteString(int addr,String str){
  for(int i=0;i<str.length();i++)
  {
    EEPROM.write(addr++,str[i]);
  }
  EEPROM.write(addr++,'\n');
  EEPROM.commit();
}
String ReadString(int addr){
  String result="";
  while((char)EEPROM.read(addr)!='\n'&&addr<=4096)
  {
    result+=(char)EEPROM.read(addr++);
  }
  return result;
}

int NTPday;struct tm *ptm;
time_t prevEpoch=1600000000;
long unsigned loopTime;
void synchroniseTime(){
  ntp.update();
  time_t epoch = ntp.getEpochTime();
  while(epoch<prevEpoch)
  {
   ntp.update();
   epoch = ntp.getEpochTime();
  }
  prevEpoch=epoch;
  Serial.println(epoch);
  ptm = gmtime ((time_t *)&epoch);
  mktime(ptm);
  
  NTPday = (*ptm).tm_mday;
}

bool connected=false;
void connectToWifi(){
  delay(1000);
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.println("");
  Serial.print("Connecting");

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && ++attempts <= 40) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED)
  {
    Serial.print("Connected to ");
    Serial.println(ssid);
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    WiFi.setAutoReconnect(true);
    WiFi.persistent(true);
    connected = true;
  }
  else
  {
    Serial.println("Error Connecting to "+ssid);
    connected=false;
  }
}
void setup() {
  Serial.begin(9600);
  ntp.begin();
  EEPROM.begin(4096);
  delay(3000);
  int addr=0;
  String ssid_old=ReadString(addr);
  addr+=ssid_old.length()+1;
  
  String password_old=ReadString(addr);
  addr+=password_old.length()+1;
  
  String server_ip_old=ReadString(addr);
  
  String ssid_new;
  String password_new;
  while(!connected){
    ssid_new=prompt("SSID:");
    password_new=prompt("password:");
    while((password_new=="unresponsive")!=(ssid_new=="unresponsive"))
    {
      Serial.println("You must enter both an SSID and a password");
      ssid_new=prompt("SSID:");
      password_new=prompt("password:");
    }
    if(ssid_new=="unresponsive")
    {
      ssid=ssid_old;
      password=password_old;
      connectToWifi();
    }
    else 
    {
      if(ssid_new=="old")ssid=ssid_old;
      else ssid=ssid_new;
      if(password_new=="old")password=password_old;
      else password=password_new;
      connectToWifi();
    }
  }
  String server_ip_new=prompt("server ip:");
  if(server_ip_new=="unresponsive"||server_ip_new=="old"){
    server_ip=server_ip_old;
  }
  else{
    server_ip=server_ip_new;
  }
  addr=0;
  WriteString(addr,ssid);
  
  addr+=ssid.length()+1;
  WriteString(addr,password);
  
  addr+=password.length()+1;
  WriteString(addr,server_ip);
  
  synchroniseTime();
  while (!bme.begin(0x76)) {
    Serial.println("no bme found on pin");
    delay(1000);
  }
  loopTime=millis();
}

struct tm timeStamps[720];
float dataStorage[720][3];
int dataIndex = 0;
#define lastTemp dataStorage[dataIndex][0]
#define lastHum dataStorage[dataIndex][1]
#define lastHpa dataStorage[dataIndex][2]
#define lastTM timeStamps[dataIndex]

int removeDelay=0;

void loop() {
  loopTime+=60000;
  delay(59000-removeDelay);
  while(loopTime>millis())delay(50);
  removeDelay=0;
  (*ptm).tm_min++;
  mktime(ptm);
  if ((*ptm).tm_mday != NTPday)
  {
    synchroniseTime();
  }
  float temp = bme.readTemperature();
  float hum = bme.readHumidity();
  float hpa = bme.readPressure() / 100.F;

  lastTemp = temp;
  lastHum = hum;
  lastHpa = hpa;
  lastTM = (*ptm);
  dataIndex++;
  if(dataIndex>=720)dataIndex=719;
  while (WiFi.status() == WL_CONNECTED && dataIndex-1 >= 0)
  {
    dataIndex--;
    http.begin(wifi, "http://"+server_ip + "/esp8266/postData.php");
    Serial.println("http://"+server_ip + "/esp8266/postData.php");
    removeDelay+=1000;
    delay(1000);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String post = "module=inside";
    post += "&temperature=" + String(lastTemp) + "&humidity=" + String(lastHum) + "&pressure=" + String(lastHpa);
    post += "&day=" + String(lastTM.tm_mday) + "&month=" + String(lastTM.tm_mon+1) + "&year=" + String(1900+lastTM.tm_year) + "&hour=" + String(lastTM.tm_hour) + "&minute=" + String(lastTM.tm_min);
    int resultingCode = http.POST(post);
    if(resultingCode!=200)dataIndex++;
    Serial.println(post);
    Serial.println(resultingCode);
    http.end();
  }
}
