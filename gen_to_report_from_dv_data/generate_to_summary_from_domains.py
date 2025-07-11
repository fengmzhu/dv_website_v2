#!/usr/bin/env python3
""從IT Domain和NX Domain的資料整合產生TO summary JSON
包含原始數據的對應資料
"""

import json
from pathlib import Path

def load_domain_data():
    """載入IT Domain和NX Domain的資料"""
    
    # 載入IT Domain原始資料
    it_allproject_path = Path("IT_Domain/ideal_IT_Domain_Data/it_domain_allproject.json")
    it_allproject_data = []
    if it_allproject_path.exists():
        with open(it_allproject_path, 'r', encoding='utf-8') as f:
            it_allproject_data = json.load(f)
    
    it_dv_tasks_path = Path("IT_Domain/ideal_IT_Domain_Data/it_domain_dv_tasks.json")
    it_dv_tasks_data = []
    if it_dv_tasks_path.exists():
        with open(it_dv_tasks_path, 'r', encoding='utf-8') as f:
            it_dv_tasks_data = json.load(f)
    
    # 載入IT Domain補充資料
    it_supplemental_path = Path("IT_Domain/ideal_IT_Domain_Data/it-domain-to-be-added.json")
    it_supplemental_data = []
    if it_supplemental_path.exists():
        with open(it_supplemental_path, 'r', encoding='utf-8') as f:
            it_supplemental_data = json.load(f)
    
    # 載入NX Domain原始資料
    nx_examples_path = Path("NX_Domain/ideal_NX_Domain_Data/nx_domain_examples.json")
    nx_examples_data = []
    if nx_examples_path.exists():
        with open(nx_examples_path, 'r', encoding='utf-8') as f:
            nx_examples_data = json.load(f)
    
    # 載入NX Domain補充資料
    nx_supplemental_path = Path("NX_Domain/ideal_NX_Domain_Data/nx-domain-to-be-added.json")
    nx_supplemental_data = []
    if nx_supplemental_path.exists():
        with open(nx_supplemental_path, 'r', encoding='utf-8') as f:
            nx_supplemental_data = json.load(f)
    
    return it_allproject_data, it_dv_tasks_data, it_supplemental_data, nx_examples_data, nx_supplemental_data

def find_matching_project_data(project_name, it_allproject_data, it_dv_tasks_data):
    """在IT Domain原始資料中尋找對應的項目資料 - 優先返回dv_tasks資料"""
    
    # 優先在dv_tasks資料中尋找
    for item in it_dv_tasks_data:
        if item['data'].get('Project') == project_name:
            return item
    
    # 如果沒有找到dv_tasks資料，再在allproject資料中尋找
    for item in it_allproject_data:
        if item['data'].get('Project') == project_name:
            return item
    
    return None

def find_matching_nx_data(project_name, nx_examples_data):
    """在NX Domain原始資料中尋找對應的項目資料"""
    
    for item in nx_examples_data:
        data = item['data']
        if data.get('PROJECT') == project_name or data.get('IP') == project_name:
            return item
    
    return None

def create_to_summary_records(it_allproject_data, it_dv_tasks_data, it_supplemental_data, nx_examples_data, nx_supplemental_data):
    """創建TO summary記錄"""
    
    to_summary_records = []
    
    # 從原始資料中獲取實際的項目名稱
    projects = []
    
    # 優先從IT Domain dv_tasks資料獲取項目名稱（這是主要的DV任務資料）
    for item in it_dv_tasks_data:
        project_name = item['data'].get('Project')
        if project_name and project_name not in projects:
            projects.append(project_name)
    
    # 從IT Domain allproject資料獲取項目名稱（作為補充）
    for item in it_allproject_data:
        project_name = item['data'].get('Project')
        if project_name and project_name not in projects:
            projects.append(project_name)
    
    print(f"找到的項目: {projects}")
    
    for i, project_name in enumerate(projects):
        
        # 尋找IT Domain原始資料 - 優先使用dv_tasks資料
        it_original = find_matching_project_data(project_name, it_allproject_data, it_dv_tasks_data)
        
        # 尋找NX Domain原始資料
        nx_original = find_matching_nx_data(project_name, nx_examples_data)
        
        # 獲取補充資料
        it_supplemental = it_supplemental_data[i] if i < len(it_supplemental_data) else None
        nx_supplemental = nx_supplemental_data[i] if i < len(nx_supplemental_data) else None
        
        # 整合項目信息
        project_info = {
            "Project": project_name,
            "IP": "",
            "IP Postfix": "",
            "Alternative Name": ""
        }
        
        # 優先從IT Domain dv_tasks資料獲取基本信息
        dv_task_data = None
        allproject_data = None
        
        # 尋找dv_tasks資料
        for item in it_dv_tasks_data:
            if item['data'].get('Project') == project_name:
                dv_task_data = item['data']
                break
        
        # 尋找allproject資料
        for item in it_allproject_data:
            if item['data'].get('Project') == project_name:
                allproject_data = item['data']
                break
        
        # 優先使用dv_tasks資料，如果沒有則使用allproject資料
        if dv_task_data:
            project_info["Project"] = dv_task_data.get('Project', project_name)
            project_info["IP"] = dv_task_data.get('IP', '')
            project_info["IP Postfix"] = dv_task_data.get('IP Postfix', "") if dv_task_data else (it_supplemental["data"].get("IP Postfix", "") if it_supplemental else "")
            project_info["Alternative Name"] = dv_task_data.get('Alternative Name', '')
        elif allproject_data:
            project_info["Project"] = allproject_data.get('Project', project_name)
            
            # 處理IP欄位
            ip_value = allproject_data.get('IP', '')
            if ip_value and ip_value != 'IP':
                if ":" in ip_value:
                    parts = ip_value.split(":")
                    if len(parts) >= 2:
                        project_info["IP"] = parts[0].strip()
                        project_info["IP Postfix"] = parts[1].strip()
                else:
                    project_info["IP"] = ip_value
            
            project_info["Alternative Name"] = allproject_data.get('Summary', '')
        
        # 如果沒有找到原始資料，使用空值
        if not project_info["IP"]:
            project_info["IP"] = ""
        if not project_info["IP Postfix"]:
            project_info["IP Postfix"] = ""
        if not project_info["Alternative Name"]:
            project_info["Alternative Name"] = ""
        
        # 整合DV任務資訊
        dv_info = {
            "DV": "",
            "TO Date": "",
            "Status": "",
            "Progress": "",
            "SPIP": "",
            "WIKI": "",
            "BU": ""
        }
        
        if dv_task_data:
            dv_info["DV"] = dv_task_data.get('DV', '')
            dv_info["TO Date"] = dv_task_data.get('TO Date', '')
            dv_info["Status"] = dv_task_data.get('Status', '')
            dv_info["Progress"] = dv_task_data.get('Progress', '')
            dv_info["SPIP"] = dv_task_data.get('SPIP', '')
            dv_info["WIKI"] = dv_task_data.get('WIKI', '')
            dv_info["BU"] = dv_task_data.get('BU', '')
        
        # 整合NX Domain基本技術信息
        nx_basic_info = {
            "Line Coverage": "",
            "FSM Coverage": "",
            "Interface Toggle Coverage": "",
            "Toggle Coverage": ""
        }
        
        if nx_original:
            data = nx_original['data']
            nx_basic_info["Line Coverage"] = data.get('COVERAGE', '')
        
        # 如果沒有找到原始資料，使用空值
        if not nx_basic_info["Line Coverage"]:
            nx_basic_info["Line Coverage"] = ""
        
        # 整合所有資料創建TO summary記錄
        to_record = {
            "Index": str(dv_task_data.get('Index', "")) if dv_task_data else "",
            "Project": project_info["Project"],
            "SPIP_IP": allproject_data.get('IP', "") if allproject_data else "",
            "IP": project_info["IP"],
            "IP Postfix": it_supplemental["data"].get("IP Postfix", "") if it_supplemental else "",
            "IP Subtype": it_supplemental["data"].get("IP Subtype", "") if it_supplemental else "",
            "Alternative Name": project_info["Alternative Name"],
            "Line Coverage": nx_supplemental["data"].get("Line Coverage", "") if nx_supplemental else "",
            "FSM Coverage": nx_supplemental["data"].get("FSM Coverage", "") if nx_supplemental else "",
            "Interface Toggle Coverage": nx_supplemental["data"].get("Interface Toggle Coverage", "") if nx_supplemental else "",
            "Toggle Coverage": nx_supplemental["data"].get("Toggle Coverage", "") if nx_supplemental else "",
            "Coverage Report Path": nx_supplemental["data"].get("Coverage Report Path", "") if nx_supplemental else "",
            "DV": dv_info["DV"],
            "DD": dv_task_data.get('DD', "") if dv_task_data else "",
            "BU": dv_info["BU"] if dv_info["BU"] else "",
            "sanity SVN": nx_supplemental["data"].get("sanity SVN", "") if nx_supplemental else "",
            "sanity SVN ver": nx_supplemental["data"].get("sanity SVN ver", "") if nx_supplemental else "",
            "release SVN": nx_supplemental["data"].get("release SVN", "") if nx_supplemental else "",
            "release SVN ver": nx_supplemental["data"].get("release SVN ver", "") if nx_supplemental else "",
            "git path": nx_supplemental["data"].get("git path", "") if nx_supplemental else "",
            "git version": nx_supplemental["data"].get("git version", "") if nx_supplemental else "",
            "golden checklist": nx_supplemental["data"].get("golden checklist", "") if nx_supplemental else "",
            "golden checklist version": nx_supplemental["data"].get("golden checklist version", "") if nx_supplemental else "",
            "TO Date": nx_supplemental["data"].get("TO Date", "") if nx_supplemental else "",
            "SPIP url": dv_info["SPIP"],
            "Wiki url": dv_info["WIKI"],
            "spec version": it_supplemental["data"].get("spec version", "") if it_supplemental else "",
            "spec path": it_supplemental["data"].get("spec path", "") if it_supplemental else "",
            "RTL last update timestamp": nx_supplemental["data"].get("RTL last update timestamp", "") if nx_supplemental else "",
            "TO report creation timestamp": nx_supplemental["data"].get("TO report creation timestamp", "") if nx_supplemental else "",
            "AD": it_supplemental["data"].get("AD", "") if it_supplemental else "",
            "Inherit from IP": it_supplemental["data"].get("Inherit from IP", "") if it_supplemental else "",
            "re-use IP": it_supplemental["data"].get("re-use IP", "") if it_supplemental else ""
        }
        
        to_summary_records.append({
            "example_id": f"TO_Summary_Example_{i+1}",
            "project": project_info["Project"],
            "ip": project_info["IP"],
            "it_domain_original": it_original,
            "it_domain_supplemental": it_supplemental,
            "nx_domain_original": nx_original,
            "nx_domain_supplemental": nx_supplemental,
            "to_summary": to_record
        })
    
    return to_summary_records

def save_to_summary_json(to_summary_records):
    """保存TO summary記錄到JSON檔案"""
    
    output_path = Path("to_summary_examples.json")
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(to_summary_records, f, ensure_ascii=False, indent=2)
    
    print(f"文件: {output_path}")
    
    # 生成欄位對應表
    generate_field_mapping_csv(to_summary_records)

def generate_field_mapping_csv(to_summary_records):
    """生成CSV格式的欄位對應表，來源邏輯與summary一致"""
    
    # 載入 allproject 資料用於 SPIP_IP 來源判斷
    it_allproject_path = Path("IT_Domain/ideal_IT_Domain_Data/it_domain_allproject.json")
    it_allproject_data = []
    if it_allproject_path.exists():
        with open(it_allproject_path, 'r', encoding='utf-8') as f:
            it_allproject_data = json.load(f)
    
    # 定義TO summary的33個標準欄位
    to_summary_fields = [
        "Index", "Project", "SPIP_IP", "IP", "IP Postfix", "IP Subtype", "Alternative Name",
        "Line Coverage", "FSM Coverage", "Interface Toggle Coverage", "Toggle Coverage", 
        "Coverage Report Path", "DV", "DD", "BU", "sanity SVN", "sanity SVN ver",
        "release SVN", "release SVN ver", "git path", "git version", "golden checklist",
        "golden checklist version", "TO Date", "SPIP url", "Wiki url", "spec version",
        "spec path", "RTL last update timestamp", "TO report creation timestamp",
        "AD", "Inherit from IP", "re-use IP"
    ]
    
    csv_content = []
    # CSV 標題行
    csv_content.append("Project,TO_Summary_Field,TO_Summary_Value,Source_File,Original_Field,Original_Value")
    
    for record in to_summary_records:
        project_name = record['project']
        to_summary = record['to_summary']
        it_original = record['it_domain_original']
        it_supplemental = record['it_domain_supplemental']
        nx_original = record['nx_domain_original']
        nx_supplemental = record['nx_domain_supplemental']
        dv_task_data = it_original['data'] if it_original and it_original['source'] == 'dv_tasks.xlsx' else None
        
        for field in to_summary_fields:
            to_value = to_summary.get(field, "")
            source_file = ""
            original_field = ""
            original_value = ""

            # 來源邏輯與 summary 產生時一致
            if field == "Index":
                if dv_task_data and "Index" in dv_task_data:
                    source_file = "it_domain_dv_tasks.json"
                    original_field = "Index"
                    original_value = str(dv_task_data.get("Index", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "SPIP_IP":
                # 專門從 allproject 資料中取得 SPIP_IP
                allproject_item = None
                for item in it_allproject_data:
                    if item['data'].get('Project') == project_name:
                        allproject_item = item
                        break
                
                if allproject_item and "IP" in allproject_item['data']:
                    source_file = "it_domain_allproject.json"
                    original_field = "IP"
                    original_value = str(allproject_item['data'].get("IP", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "DD":
                if dv_task_data and "DD" in dv_task_data and dv_task_data.get("DD", ""):
                    source_file = "it_domain_dv_tasks.json"
                    original_field = "DD"
                    original_value = str(dv_task_data.get("DD", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "BU":
                if dv_task_data and "BU" in dv_task_data and dv_task_data.get("BU"):
                    source_file = "it_domain_dv_tasks.json"
                    original_field = "BU"
                    original_value = str(dv_task_data.get("BU", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "IP Postfix":
                if it_supplemental and "IP Postfix" in it_supplemental["data"]:
                    source_file = it_supplemental["source"].split("/")[-1]
                    original_field = "IP Postfix"
                    original_value = str(it_supplemental["data"].get("IP Postfix", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "Coverage Report Path":
                if nx_supplemental and "Coverage Report Path" in nx_supplemental["data"]:
                    source_file = nx_supplemental["source"].split("/")[-1]
                    original_field = "Coverage Report Path"
                    original_value = str(nx_supplemental["data"].get("Coverage Report Path", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field in ["sanity SVN", "sanity SVN ver", "release SVN", "release SVN ver", "git path", "git version", "golden checklist", "golden checklist version", "RTL last update timestamp", "TO report creation timestamp"]:
                if nx_supplemental and field in nx_supplemental["data"]:
                    source_file = nx_supplemental["source"].split("/")[-1]
                    original_field = field
                    original_value = str(nx_supplemental["data"].get(field, ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            elif field == "TO Date":
                if nx_supplemental and "TO Date" in nx_supplemental["data"]:
                    source_file = nx_supplemental["source"].split("/")[-1]
                    original_field = "TO Date"
                    original_value = str(nx_supplemental["data"].get("TO Date", ""))
                else:
                    source_file = "預設值/計算值"
                    original_field = "N/A"
                    original_value = "N/A"
            else:
                # 其餘欄位維持原本優先順序
                if it_original and field in it_original['data']:
                    # 根據實際檔案類型顯示正確的檔案名稱
                    if it_original['source'] == 'dv_tasks.xlsx':
                        source_file = "it_domain_dv_tasks.json"
                    elif it_original['source'] == 'allproject-2025-06-23-09-32-12.xlsx':
                        source_file = "it_domain_allproject.json"
                    else:
                        source_file = it_original['source'].split("/")[-1]
                    original_field = field
                    original_value = str(it_original['data'].get(field, ""))
                elif it_supplemental and field in it_supplemental['data']:
                    source_file = it_supplemental["source"].split("/")[-1]
                    original_field = field
                    original_value = str(it_supplemental['data'].get(field, ""))
                elif nx_original and field in nx_original['data']:
                    source_file = nx_original['source'].split("/")[-1]
                    original_field = field
                    original_value = str(nx_original['data'].get(field, ""))
                elif nx_supplemental and field in nx_supplemental['data']:
                    source_file = nx_supplemental['source'].split("/")[-1]
                    original_field = field
                    original_value = str(nx_supplemental['data'].get(field, ""))
                else:
                    # 特殊處理某些欄位
                    if field == "SPIP url" and it_original and "SPIP" in it_original['data']:
                        if it_original['source'] == 'dv_tasks.xlsx':
                            source_file = "it_domain_dv_tasks.json"
                        else:
                            source_file = it_original['source'].split("/")[-1]
                        original_field = "SPIP"
                        original_value = str(it_original['data'].get("SPIP", ""))
                    elif field == "Wiki url" and it_original and "WIKI" in it_original['data']:
                        if it_original['source'] == 'dv_tasks.xlsx':
                            source_file = "it_domain_dv_tasks.json"
                        else:
                            source_file = it_original['source'].split("/")[-1]
                        original_field = "WIKI"
                        original_value = str(it_original['data'].get("WIKI", ""))
                    elif field == "DV" and it_original and "DV" in it_original['data']:
                        if it_original['source'] == 'dv_tasks.xlsx':
                            source_file = "it_domain_dv_tasks.json"
                        else:
                            source_file = it_original['source'].split("/")[-1]
                        original_field = "DV"
                        original_value = str(it_original['data'].get("DV", ""))
                    elif field == "BU" and it_original and "BU" in it_original['data']:
                        if it_original['source'] == 'dv_tasks.xlsx':
                            source_file = "it_domain_dv_tasks.json"
                        else:
                            source_file = it_original['source'].split("/")[-1]
                        original_field = "BU"
                        original_value = str(it_original['data'].get("BU", ""))
                    else:
                        source_file = "預設值/計算值"
                        original_field = "N/A"
                        original_value = "N/A"
            
            # 處理 CSV 中的特殊字符
            def escape_csv_value(value):
                if value is None:
                    return ""
                value_str = str(value)
                if ',' in value_str or '"' in value_str or '\n' in value_str:
                    # 如果包含逗號、引號或換行符，用雙引號包圍並轉義內部引號
                    return '"' + value_str.replace('"', '""') + '"'
                return value_str
            
            csv_line = f"{escape_csv_value(project_name)},{escape_csv_value(field)},{escape_csv_value(to_value)},{escape_csv_value(source_file)},{escape_csv_value(original_field)},{escape_csv_value(original_value)}"
            csv_content.append(csv_line)
    
    # 保存 CSV 檔案
    csv_path = Path("to_summary_field_mapping.csv")
    with open(csv_path, 'w', encoding='utf-8') as csvfile:
        csvfile.write('\n'.join(csv_content))
    print(f"欄位對應表: {csv_path}")

def main():
    """主函數"""
    print("=== 從IT Domain和NX Domain產生TO Summary ===")
    
    # 載入資料
    it_allproject_data, it_dv_tasks_data, it_supplemental_data, nx_examples_data, nx_supplemental_data = load_domain_data()
    
    print(f"載入IT Domain allproject資料: {len(it_allproject_data)} 筆")
    print(f"載入IT Domain dv_tasks資料: {len(it_dv_tasks_data)} 筆")
    print(f"載入IT Domain補充資料: {len(it_supplemental_data)} 筆")
    print(f"載入NX Domain原始資料: {len(nx_examples_data)} 筆")
    print(f"載入NX Domain補充資料: {len(nx_supplemental_data)} 筆")
    
    # 創建TO summary記錄
    to_summary_records = create_to_summary_records(it_allproject_data, it_dv_tasks_data, it_supplemental_data, nx_examples_data, nx_supplemental_data)
    
    # 保存結果
    save_to_summary_json(to_summary_records)
    
    print(f"\n=== 完成 ===")
    print(f"已產生 {len(to_summary_records)} 筆TO Summary記錄")
    
    # 顯示記錄摘要
    for i, record in enumerate(to_summary_records):
        project_name = record['project']
        ip_name = record['ip']
        to_summary = record['to_summary']
        
        print(f"\n記錄 {i+1}: {project_name} - {ip_name}")
        print(f"  DD: {to_summary.get('DD', 'N/A')} (Digital Designer)")
        print(f"  AD: {to_summary.get('AD', 'N/A')} (Analog Designer)")
        print(f"  BU: {to_summary.get('BU', 'N/A')} (Business Unit)")
        print(f"  IP Subtype: {to_summary.get('IP Subtype', 'N/A')}")
        print(f"  Line Coverage: {to_summary.get('Line Coverage', 'N/A')}")
        print(f"  FSM Coverage: {to_summary.get('FSM Coverage', 'N/A')}")
        print(f"  Git Version: {to_summary.get('git version', 'N/A')} (來自NX Domain)")
        
        # 顯示原始資料來源
        if record['it_domain_original']:
            print(f"  IT Domain原始資料: {record['it_domain_original']['source']}")
        if record['nx_domain_original']:
            print(f"  NX Domain原始資料: {record['nx_domain_original']['source']}")

if __name__ == "__main__":
    main() 