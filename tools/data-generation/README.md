# DV Data Analysis and TO Summary Generation Tool

這個工具用於整合 IT Domain 和 NX Domain 的 DV (Design Verification) 數據，生成標準化的 TO (Tape Out) Summary 報告。

## 專案概述

### 背景
在 IC 設計流程中，DV 數據分散在不同的系統中：
- **IT Domain**: 外網系統，包含項目管理、任務分配、進度追蹤等數據
- **NX Domain**: 內網系統，包含技術測試結果、覆蓋率、SVN/Git 版本控制等數據

### 目標
將兩個 Domain 的數據整合成統一的 TO Summary 格式，包含 32 個標準欄位，提供完整的項目視圖。

## 目錄結構

```
gen_to_report_from_dv_data/
├── IT_Domain/
│   └── ideal_IT_Domain_Data/
│       ├── it_domain_allproject.json      # IT Domain 項目管理數據
│       ├── it_domain_dv_tasks.json        # IT Domain DV任務數據
│       └── it-domain-to-be-added.json     # 需要填寫的IT Domain補充數據
├── NX_Domain/
│   └── ideal_NX_Domain_Data/
│       ├── nx_domain_examples.json        # NX Domain 原始數據示例
│       └── nx-domain-to-be-added.json     # 需要填寫的NX Domain補充數據
├── generate_to_summary_from_domains.py    # 主要整合腳本
├── requirements.txt                       # Python 依賴包
└── README.md                             # 本文件
```

## 數據理解

### 1. IT Domain 數據

#### 1.1 allproject 數據 (it_domain_allproject.json)
**來源**: `allproject-2025-06-23-09-32-12.xlsx`
**用途**: 項目基本信息和狀態管理

**主要欄位**:
- `Project`: 項目名稱 (如 RLE1339, RL1234)
- `IP`: IP 名稱 (如 AFE, pcie)
- `Summary`: 項目摘要描述
- `Status`: 項目狀態

#### 1.2 dv_tasks 數據 (it_domain_dv_tasks.json)
**來源**: `dv_tasks.xlsx`
**用途**: DV 任務詳細信息和進度追蹤

**主要欄位**:
- `Index`: 任務索引號
- `Project`: 項目名稱
- `IP`: IP 名稱
- `IP Postfix`: IP 後綴 (如 "2x1", 但並不是參考dv_tasks內容，實際由nx domain database資料決定)
- `DV`: DV 工程師代號 (如 LI, CH)
- `DD`: Digital Designer (如 Jimmy, Ramon)
- `BU`: Business Unit (如 CN, PC)
- `TO Date`: Tape Out 日期
- `Status`: 任務狀態 (如 done, in progress)
- `Progress`: 進度百分比
- `SPIP`: SPIP 系統連結
- `WIKI`: Wiki 頁面連結

#### 1.3 補充數據 (it-domain-to-be-added.json)
**用途**: 需要手動填寫的額外 IT Domain 信息

**主要欄位**:
- `IP Subtype`: IP 子類型 (如 default, gen2x1)
- `DD`: Digital Designer (優先於 dv_tasks)
- `BU`: Business Unit (如果 dv_tasks 中沒有)
- `AD`: Analog Designer
- `spec version`: 規格版本
- `spec path`: 規格文件路徑
- `Inherit from IP`: 繼承自哪個 IP
- `re-use IP`: 是否重用 IP (Y/N)

### 2. NX Domain 數據

#### 2.1 原始數據 (nx_domain_examples.json)
**來源**: 多個內網數據庫和文件
- `MySQL_rdc_dv_tape_out_lookup.csv`: 內網數據庫查詢結果
- `MySQL_rdc_dv_report_hist.csv`: DV 報告歷史數據
- `nx/golden_checklist.xls`: 內網檢查清單

**主要欄位**:
- `IP`: 項目名稱
- `TIMESTAMP`: 時間戳
- `TPO_DATE`: Tape Out 日期
- `REG_FLAG`: 註冊標誌
- `COVERAGE`: 覆蓋率數據

#### 2.2 補充數據 (nx-domain-to-be-added.json)
**用途**: 需要手動填寫的額外 NX Domain 信息

**主要欄位**:
- `Line Coverage`: 行覆蓋率
- `FSM Coverage`: FSM 覆蓋率
- `Interface Toggle Coverage`: 介面切換覆蓋率
- `Toggle Coverage`: 切換覆蓋率
- `Coverage Report Path`: 覆蓋率報告路徑
- `sanity SVN`: Sanity 測試 SVN 路徑
- `sanity SVN ver`: Sanity 測試 SVN 版本
- `release SVN`: 發布 SVN 路徑
- `release SVN ver`: 發布 SVN 版本
- `git path`: Git 倉庫路徑
- `git version`: Git 版本
- `golden checklist`: Golden 檢查清單路徑
- `golden checklist version`: Golden 檢查清單版本
- `RTL last update timestamp`: RTL 最後更新時間
- `TO report creation timestamp`: TO 報告創建時間
- `TO Date`: Tape Out 日期 (優先於 IT Domain)

## TO Summary 32個標準欄位

### 基本信息 (1-7)
1. **Index**: 索引號 (來源: dv_tasks)
2. **Project**: 項目名稱 (來源: dv_tasks)
3. **SPIP_IP**: SPIP IP 名稱 (來源: it_domain_allproject.json)
4. **IP**: IP 名稱 (來源: dv_tasks)
5. **IP Postfix**: IP 後綴 (來源: it-domain-to-be-added)
6. **IP Subtype**: IP 子類型 (來源: it-domain-to-be-added)
7. **Alternative Name**: 替代名稱 (來源: dv_tasks)

### 覆蓋率信息 (8-12)
8. **Line Coverage**: 行覆蓋率 (來源: nx-domain-to-be-added)
9. **FSM Coverage**: FSM 覆蓋率 (來源: nx-domain-to-be-added)
10. **Interface Toggle Coverage**: 介面切換覆蓋率 (來源: nx-domain-to-be-added)
11. **Toggle Coverage**: 切換覆蓋率 (來源: nx-domain-to-be-added)
12. **Coverage Report Path**: 覆蓋率報告路徑 (來源: nx-domain-to-be-added)

### 人員信息 (13-15)
13. **DV**: DV 工程師 (來源: dv_tasks)
14. **DD**: Digital Designer (來源: dv_tasks 優先)
15. **BU**: Business Unit (來源: dv_tasks 優先)

### 版本控制信息 (16-23)
16. **sanity SVN**: Sanity 測試 SVN (來源: nx-domain-to-be-added)
17. **sanity SVN ver**: Sanity 測試 SVN 版本 (來源: nx-domain-to-be-added)
18. **release SVN**: 發布 SVN (來源: nx-domain-to-be-added)
19. **release SVN ver**: 發布 SVN 版本 (來源: nx-domain-to-be-added)
20. **git path**: Git 路徑 (來源: nx-domain-to-be-added)
21. **git version**: Git 版本 (來源: nx-domain-to-be-added)
22. **golden checklist**: Golden 檢查清單 (來源: nx-domain-to-be-added)
23. **golden checklist version**: Golden 檢查清單版本 (來源: nx-domain-to-be-added)

### 時間信息 (24-26)
24. **TO Date**: Tape Out 日期 (來源: nx-domain-to-be-added 優先)
25. **RTL last update timestamp**: RTL 最後更新時間 (來源: nx-domain-to-be-added)
26. **TO report creation timestamp**: TO 報告創建時間 (來源: nx-domain-to-be-added)

### 連結信息 (27-28)
27. **SPIP url**: SPIP 系統連結 (來源: dv_tasks)
28. **Wiki url**: Wiki 頁面連結 (來源: dv_tasks)

### 規格信息 (29-30)
29. **spec version**: 規格版本 (來源: it-domain-to-be-added)
30. **spec path**: 規格文件路徑 (來源: it-domain-to-be-added)

### 設計信息 (31-33)
31. **AD**: Analog Designer (來源: it-domain-to-be-added)
32. **Inherit from IP**: 繼承自 IP (來源: it-domain-to-be-added)
33. **re-use IP**: 重用 IP (來源: it-domain-to-be-added)

## 數據整合邏輯

### 優先順序規則
1. **Index**: dv_tasks → 空值
2. **IP Postfix**: it-domain-to-be-added → 空值
3. **Coverage Report Path**: nx-domain-to-be-added → 空值
4. **DD**: dv_tasks → it-domain-to-be-added → 空值
5. **BU**: dv_tasks → it-domain-to-be-added → 空值
6. **TO Date**: nx-domain-to-be-added → dv_tasks → 空值
7. **其他 NX Domain 欄位**: nx-domain-to-be-added → 空值
8. **其他 IT Domain 欄位**: 按來源優先順序

### 項目匹配邏輯
- 通過 `Project` 名稱進行匹配
- 支援多個數據源的項目對應
- 如果找不到匹配項目，會顯示警告

## 使用方法

### 1. 環境準備
```bash
# 安裝依賴包
pip install -r requirements.txt
```

### 2. 數據準備
在以下文件中填寫實際數據：

#### IT Domain 補充數據
編輯 `IT_Domain/ideal_IT_Domain_Data/it-domain-to-be-added.json`:
```json
[
  {
    "source": "it-domain-to-be-added.json",
    "data": {
      "IP Subtype": "default",
      "IP Postfix": "",
      "DD": "Ramon",
      "BU": "CN",
      "AD": "Peter",
      "spec version": "v1.0",
      "spec path": "/project/spec/RL6577_spec.pdf",
      "Inherit from IP": "RL6576",
      "re-use IP": "N"
    }
  }
]
```

#### NX Domain 補充數據
編輯 `NX_Domain/ideal_NX_Domain_Data/nx-domain-to-be-added.json`:
```json
[
  {
    "source": "nx-domain-to-be-added.json",
    "data": {
      "Line Coverage": "88.5",
      "FSM Coverage": "78.5",
      "Interface Toggle Coverage": "82.1",
      "Toggle Coverage": "79.3",
      "Coverage Report Path": "/project/coverage/RL6577_coverage.html",
      "sanity SVN": "http://dtdinfo/svn/RD/RL6577/sanity",
      "sanity SVN ver": "9876",
      "release SVN": "http://dtdinfo/svn/RD/RL6577",
      "release SVN ver": "12345",
      "git path": "ssh://git.xxx/RL6577.git",
      "git version": "v1.2.3",
      "golden checklist": "/project/golden/RL6577_checklist.xlsx",
      "golden checklist version": "2.1",
      "RTL last update timestamp": "2024-12-10 14:30:00",
      "TO report creation timestamp": "2024-12-15 09:15:00",
      "TO Date": "2024-10-15 00:00:00"
    }
  }
]
```

### 3. 執行整合
```bash
python3 generate_to_summary_from_domains.py
```

### 4. 查看結果
執行完成後會生成以下文件：
- `to_summary_examples.json`: 完整的 TO Summary 數據
- `to_summary_field_mapping.csv`: 欄位來源對應表 (CSV 格式)
- `to_summary_source_statistics.csv`: 資料來源統計 (CSV 格式)

## 輸出文件說明

### to_summary_examples.json
包含每個項目的完整數據結構：
```json
{
  "example_id": "TO_Summary_Example_1",
  "project": "RLE1339",
  "ip": "AFE",
  "it_domain_original": { ... },
  "it_domain_supplemental": { ... },
  "nx_domain_original": { ... },
  "nx_domain_supplemental": { ... },
  "to_summary": { ... }
}
```

### to_summary_field_mapping.csv
詳細記錄每個 TO Summary 欄位的來源信息，包含以下欄位：
- **Project**: 項目名稱
- **TO_Summary_Field**: TO Summary 欄位名稱
- **TO_Summary_Value**: TO Summary 中的數值
- **Source_File**: 數據來源檔案
- **Original_Field**: 原始數據中的欄位名稱
- **Original_Value**: 原始數據中的數值

### to_summary_source_statistics.csv
統計各數據源的使用情況：
- **Source_File**: 數據來源檔案名稱
- **Field_Count**: 該來源提供的欄位數量

## 數據驗證

### 檢查項目
1. **數據完整性**: 確認所有必要欄位都有值
2. **來源一致性**: 確認 mapping table 與實際數據一致
3. **優先順序**: 確認數據來源優先順序正確
4. **格式正確性**: 確認日期、數值格式正確

### 常見問題
1. **空值處理**: 如果某個欄位在所有來源中都沒有值，會顯示為空字串或 NaN
2. **日期格式**: 確保日期格式統一 (YYYY-MM-DD HH:MM:SS)
3. **數值格式**: 覆蓋率等數值保持原始格式，不進行計算

## 擴展功能

### 自定義欄位
可以在 `generate_to_summary_from_domains.py` 中添加新的欄位：
1. 在 `to_summary_fields` 列表中添加欄位名
2. 在 `to_record` 字典中添加數據整合邏輯
3. 在 mapping table 生成邏輯中添加來源判斷

### 新增數據源
1. 在 `load_domain_data()` 函數中載入新數據源
2. 在 `create_to_summary_records()` 函數中添加整合邏輯
3. 更新優先順序規則

## 注意事項

1. **數據安全**: 確保敏感數據不會被意外暴露
2. **備份**: 在修改原始數據前先備份
3. **版本控制**: 使用 Git 追蹤數據和程式碼變更
4. **測試**: 在生產環境使用前先在測試環境驗證

## 技術細節

### 程式架構
- **模組化設計**: 每個功能都有獨立的函數
- **錯誤處理**: 包含完整的錯誤處理機制
- **日誌記錄**: 詳細的執行日誌
- **數據驗證**: 自動驗證數據完整性

### 性能考量
- **記憶體使用**: 大數據集時考慮分批處理
- **執行時間**: 複雜查詢可能需要優化
- **並發處理**: 支援多項目並行處理

## 維護和更新

### 定期維護
1. 更新依賴包版本
2. 檢查數據格式變更
3. 驗證整合邏輯正確性
4. 更新文檔

### 版本歷史
- v1.0: 初始版本，支援基本數據整合
- v1.1: 添加欄位來源追蹤
- v1.2: 優化優先順序邏輯
- v1.3: 添加數據驗證功能

## 聯絡資訊

如有問題或建議，請聯繫開發團隊。 