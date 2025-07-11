# TO Summary 數據來源流程圖

## 數據來源架構

```mermaid
graph TB
    %% 原始數據源
    subgraph "原始數據源"
        A1[allproject-2025-06-23-09-32-12.xlsx]
        A2[dv_tasks.xlsx]
        A3[MySQL_rdc_dv_tape_out_lookup.csv]
        A4[MySQL_rdc_dv_report_hist.csv]
        A5[nx/golden_checklist.xls]
    end
    
    %% JSON 轉換層
    subgraph "JSON 轉換層"
        B1[it_domain_allproject.json]
        B2[it_domain_dv_tasks.json]
        B3[nx_domain_examples.json]
    end
    
    %% 補充數據
    subgraph "補充數據"
        C1[it-domain-to-be-added.json]
        C2[nx-domain-to-be-added.json]
    end
    
    %% 整合處理
    subgraph "數據整合處理"
        D1[項目匹配]
        D2[優先順序處理]
        D3[欄位映射]
    end
    
    %% 輸出結果
    subgraph "TO Summary 輸出"
        E1[to_summary_examples.json]
        E2[to_summary_field_mapping.md]
        E3[to_summary_examples.csv]
    end
    
    %% 連接關係
    A1 --> B1
    A2 --> B2
    A3 --> B3
    A4 --> B3
    A5 --> B3
    
    B1 --> D1
    B2 --> D1
    B3 --> D1
    C1 --> D2
    C2 --> D2
    
    D1 --> D3
    D2 --> D3
    D3 --> E1
    D3 --> E2
    D3 --> E3
```

## 詳細欄位來源映射

```mermaid
graph LR
    subgraph "IT Domain 數據源"
        IT1[dv_tasks.xlsx]
        IT2[it_domain_allproject.json]
        IT3[it-domain-to-be-added.json]
    end
    
    subgraph "NX Domain 數據源"
        NX1[nx_domain_examples.json]
        NX2[nx-domain-to-be-added.json]
    end
    
    subgraph "TO Summary 欄位 (33個)"
        TS1[Index]
        TS2[Project]
        TS3[SPIP_IP]
        TS4[IP]
        TS5[IP Postfix]
        TS6[IP Subtype]
        TS7[Alternative Name]
        TS8[Line Coverage]
        TS9[FSM Coverage]
        TS10[Interface Toggle Coverage]
        TS11[Toggle Coverage]
        TS12[Coverage Report Path]
        TS13[DV]
        TS14[DD]
        TS15[BU]
        TS16[sanity SVN]
        TS17[sanity SVN ver]
        TS18[release SVN]
        TS19[release SVN ver]
        TS20[git path]
        TS21[git version]
        TS22[golden checklist]
        TS23[golden checklist version]
        TS24[TO Date]
        TS25[RTL last update timestamp]
        TS26[TO report creation timestamp]
        TS27[SPIP url]
        TS28[Wiki url]
        TS29[spec version]
        TS30[spec path]
        TS31[AD]
        TS32[Inherit from IP]
        TS33[re-use IP]
    end
    
    %% 連接關係
    IT1 --> TS1
    IT1 --> TS2
    IT1 --> TS4
    IT1 --> TS7
    IT1 --> TS13
    IT1 --> TS14
    IT1 --> TS15
    IT1 --> TS24
    IT1 --> TS27
    IT1 --> TS28
    
    IT2 --> TS3
    
    IT3 --> TS5
    IT3 --> TS6
    IT3 --> TS14
    IT3 --> TS15
    IT3 --> TS29
    IT3 --> TS30
    IT3 --> TS31
    IT3 --> TS32
    IT3 --> TS33
    
    NX2 --> TS8
    NX2 --> TS9
    NX2 --> TS10
    NX2 --> TS11
    NX2 --> TS12
    NX2 --> TS16
    NX2 --> TS17
    NX2 --> TS18
    NX2 --> TS19
    NX2 --> TS20
    NX2 --> TS21
    NX2 --> TS22
    NX2 --> TS23
    NX2 --> TS24
    NX2 --> TS25
    NX2 --> TS26
```

## 優先順序規則

```mermaid
flowchart TD
    A[開始數據整合] --> B{檢查欄位類型}
    
    B -->|Index| C[從 dv_tasks.xlsx 取得]
    B -->|SPIP_IP| D[從 it_domain_allproject.json 取得]
    B -->|DD| E{檢查 dv_tasks.xlsx}
    B -->|BU| F{檢查 dv_tasks.xlsx}
    B -->|IP Postfix| G[從 it-domain-to-be-added.json 取得]
    B -->|Coverage Report Path| H[從 nx-domain-to-be-added.json 取得]
    B -->|NX Domain 欄位| I[從 nx-domain-to-be-added.json 取得]
    B -->|TO Date| J{檢查 nx-domain-to-be-added.json}
    B -->|其他欄位| K[按來源優先順序]
    
    E -->|有值| E1[使用 dv_tasks.xlsx]
    E -->|無值| E2[使用 it-domain-to-be-added.json]
    
    F -->|有值| F1[使用 dv_tasks.xlsx]
    F -->|無值| F2[使用 it-domain-to-be-added.json]
    
    J -->|有值| J1[使用 nx-domain-to-be-added.json]
    J -->|無值| J2[使用 dv_tasks.xlsx]
    
    K --> K1[IT Domain 原始資料]
    K --> K2[IT Domain 補充資料]
    K --> K3[NX Domain 原始資料]
    K --> K4[NX Domain 補充資料]
    
    C --> L[生成 TO Summary]
    D --> L
    E1 --> L
    E2 --> L
    F1 --> L
    F2 --> L
    G --> L
    H --> L
    I --> L
    J1 --> L
    J2 --> L
    K1 --> L
    K2 --> L
    K3 --> L
    K4 --> L
    
    L --> M[輸出 JSON, MD, CSV]
```

## 項目匹配邏輯

```mermaid
graph TD
    A[開始項目匹配] --> B[載入所有項目名稱]
    
    B --> C[從 dv_tasks.xlsx 獲取項目]
    B --> D[從 allproject.xlsx 獲取項目]
    
    C --> E[合併項目列表]
    D --> E
    
    E --> F[對每個項目進行處理]
    
    F --> G[尋找 IT Domain 原始資料]
    F --> H[尋找 NX Domain 原始資料]
    F --> I[獲取補充資料]
    
    G --> J{IT Domain 資料來源}
    J -->|dv_tasks.xlsx| K[優先使用 dv_tasks 資料]
    J -->|allproject.xlsx| L[使用 allproject 資料]
    
    K --> M[整合所有資料]
    L --> M
    H --> M
    I --> M
    
    M --> N[生成 TO Summary 記錄]
    N --> O[保存到輸出檔案]
```

## 數據流程總結

1. **原始數據收集**: 從多個 Excel 和 CSV 檔案收集原始數據
2. **JSON 轉換**: 將原始數據轉換為結構化的 JSON 格式
3. **補充數據**: 手動填寫額外的補充資訊
4. **項目匹配**: 根據項目名稱匹配不同來源的數據
5. **優先順序處理**: 按照預定義的優先順序規則整合數據
6. **欄位映射**: 將來源數據映射到 TO Summary 的 33 個標準欄位
7. **輸出生成**: 產生 JSON、Markdown 和 CSV 格式的結果

這個流程確保了數據的完整性和一致性，同時提供了清晰的數據來源追蹤。 