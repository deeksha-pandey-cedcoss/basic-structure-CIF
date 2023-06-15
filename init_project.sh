#!/bin/bash

while true
do

#project_name="hubspot"
echo -e "\e[1;32m"
read -p  "Enter your project name:- " project_name  
echo -e "  \e[0m"

#copy ssh key from current user and root to .ssh_git
echo "Copying ssh key from $USER user and root to .ssh_git"
cp /home/$USER/.ssh/* ./ssh_git
sudo cp /root/.ssh/. -r ssh_git
### sudo rm ./.ssh_git/known_hosts  &>/dev/null

#project_name="onyx"

echo "Searching for $project_name..."

base_url="https://ejn1tpwib7.execute-api.us-east-2.amazonaws.com/default/envSetup_composer_apiGateway"
#result=$(curl -X GET --header "Accept: */*" "$base_url ?action=get_project_list")

url_params="action=search_project&value=$project_name"

# Api caller method starting....................................................................................
api_caller(){
url="${base_url}?$1"
#url="${base_url}?action=search_project&value=$project_name"
echo -e "\e[1;32mCalling API:- $url  \e[0m"
# result=$(curl -X GET --header "Accept: */*" $url )
response=$(curl -s -w " %{http_code}"  "$url")
http_code="${response##* }"  # get the last line
#data=  "$response" | awk '{$NF="";sub(/[ \t]+$/,"")}1'  # get all but the last line which contains the status code
data=$(echo "$response" | sed "s/200/ /")
# echo $http_code
# echo $data
#tput clear
if [[ $http_code -eq 200  ]] ; then
#echo -e "\e[1;34m Content:- $data \e[0m"
eval "$2='$data'"

else
echo -e "\e[1;31m Unable to complete api calling, please try again later. Status code:- $http_code \e[0m"
eval "$2=' '"
fi
}
# Api caller method ending....................................................................................

return_var=''
api_caller $url_params return_var
sudo apt install jq -y  &>/dev/null

# Handling cases according to msg from api...........

success_msg="Search completed successfully."
select_msg="Exact match not found for your project name, please choose project name from available project list."
api_msg=$(echo $return_var | jq -r '.msg')

if [[ "$api_msg" == "$success_msg" ]] ; then
echo -e "\e[1;34m\nINFO:- "
echo $return_var | jq -r '.msg'
echo -e "\e[0m"
#echo $return_var | jq -r '.data.projects[]'
# echo "Projet Id   Project Name      Description"
# echo "---------   ------------      ..........."
echo -e "\e[1;34mSN   Project Name\e[0m"
echo -e "\e[1;34m--   ------------\e[0m"
count=1
for row in $(echo "${return_var}" | jq -r '.data.projects[] | @base64'); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }
#   echo "$(_jq '.pid')           $(_jq '.project_name')       $(_jq '.description')"
    echo "$count    $(_jq '.project_name')"
    count=$((count + 1))
done
echo ""
break

elif [[ "$api_msg" == "$select_msg" ]] ; then
echo -e "\e[1;31m\nWARNING:- "
echo $return_var | jq -r '.msg'
echo -e "\e[0m"

echo -e "\e[1;34mSN   Project Name\e[0m"
echo -e "\e[1;34m--   ------------\e[0m"
count=1
for row in $(echo "${return_var}" | jq -r '.data.projects[] | @base64'); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }
    echo "$count    $(_jq '.project_name')"
    count=$((count + 1))
done
echo ""
else
echo "Something went wrong."
fi

done


# Creating blank directory for project name....................................

echo -e "\e[1;34mINFO:- Initiating project setup... \e[0m"
echo ""
#sudo mkdir /var/www/html/$project_name -p
#echo "Project folder created."

#sudo chown $USER:$USER  /var/www/html/$project_name -R
sudo chown $USER:$USER  . -R
echo "Ownership changed ($USER:$USER) for - $PWD/."
#sudo chmod 777 -R /var/www/html/$project_name
sudo chmod 777 -R .
echo "Permission changed (777) for - $PWD/." 
echo ""
#Handling apps inside project.........................
#app listing.............
echo -e "\e[1;34mSN   App Name\e[0m"
echo -e "\e[1;34m--   ------------\e[0m"

count=1
for row in $(echo "${return_var}" | jq -r '.data.projects[0].apps[] | @base64'); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }
    echo "$count    $(_jq '.app_name')"
    count=$((count + 1))
done
echo ""

#app setup starting.............
echo -e "\e[1;32mINFO:- App setup started\e[0m"
count=0
for row in $(echo "${return_var}" | jq -r '.data.projects[0].apps[] | @base64'); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }
    echo ""
    echo -e "\e[1;34mINFO:- $(_jq '.app_name') app setup initiated\e[0m"
    mkdir ./app/"$(_jq '.app_name')" -p
    #sudo chown $USER:$USER  /var/www/html/$project_name -R
    #echo "Ownership changed. $USER:$USER"
    #sudo chmod 777 -R /var/www/html/$project_name
    #echo "Permission allowed (777)."

    #git pull initiated...............
    cd ./app/"$(_jq '.app_name')"
    git init
    echo -e "\e[1;34mINFO:- git init done\e[0m"
    echo ""
    #echo "change dir to /var/www/html/$project_name/app/$(_jq '.app_name')"
    git remote add origin "$(_jq '.git_repo')"
    echo -e "\e[1;34mINFO:- adding remote origin  $(_jq '.git_repo')\e[0m"
    
    git pull origin "$(_jq '.git_branch')"
    echo -e "\e[1;34mINFO:- pulling branch $(_jq '.git_branch') repo $(_jq '.git_repo')\e[0m"
    
    cd ../..

    if [[ "$(_jq '.composer_json_data')" != "null" ]]; then
        echo -e "\e[1;34mINFO:- Generating composer file for $(_jq '.app_name')\e[0m"
       # printf "%s" "$(_jq '.composer_json_data')" > "/var/www/html/$project_name/app/$(_jq '.app_name')/composer.json"
       # echo $PWD
        printf "%s" "$(_jq '.composer_json_data')" > "./app/$(_jq '.app_name')/composer.json"
        
        #docker restart 1664e610cf51
        echo -e "\e[1;34mINFO:- runnig composer command on $(_jq '.app_name')\e[0m"
    #    cd /var/www/html/$project_name/app/"$(_jq '.app_name')"
         docker exec -it phalcon bash -c  "cd /app/$(_jq '.app_name') && php /app/$(_jq '.app_name')/composer.phar update --ignore-platform-reqs"
        
        echo -e "\e[1;34mINFO:- Generating log (system.log) file for $(_jq '.app_name')\e[0m"
        #echo $PWD
        log_file="./app/$(_jq '.app_name')/var/log"
        mkdir -p $log_file && touch "$log_file/system.log"
        
        echo -e "\e[1;34mINFO:- Generating is-dev.flag file for $(_jq '.app_name')\e[0m"
        echo "1" >"./app/$(_jq '.app_name')/var/is-dev.flag"

    fi

    app_name="$(_jq '.app_name')" 
    config="$(_jq '.config')" 
    
  if [[ "$config" != "null" ]]; then    
    
    for row in $(echo "${return_var}" | jq -r ".data.projects[0].apps[$count].config[] | @base64"); do
    _jq() {
     echo ${row} | base64 --decode | jq -r ${1}
    }
    

    if [[ "$config" != "null" ]]; then
        echo -e "\e[1;34mINFO:- Generating Config $(_jq '.file_name') \e[0m"

      
       # echo "$(_jq '.file_name')"
       #  echo "$(_jq '.config_code')"
       # echo "/var/www/html/$project_name/app/$app_name/app/etc/$(_jq '.file_name')"
        printf "%s" "$(_jq '.config_code')" | base64 --decode > "./app/$app_name/app/etc/$(_jq '.file_name')"
    fi
    done
    count=$((count + 1))
    
    fi

done

#php composer.phar update

#cd /var/www/html/$project_name/"$(_jq '.app_name')"

#chmod 777 -R /var/www/html/$project_name

sudo chown $USER:$USER  . -R
sudo chmod 777 -R .

echo ""
echo -e "\e[1;32mINFO:- Script executed successfully, please check above prompt messages and verify your project setup. \e[0m"


exit 
