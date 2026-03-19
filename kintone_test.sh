#!/usr/bin/env bash
set -euo pipefail

############################################
# ここを編集（環境設定）
############################################
SUBDOMAIN="xxxxxx"              # 例: "example"（https://example.cybozu.com）
CLIENT_ID="xxxxxxxxxxxxxxxxxxxxx"
CLIENT_SECRET="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
REDIRECT_URI="https://localhost:8443/auth/cybozu/callback"  # OAuthクライアントに登録したものと完全一致
APP_ID="8"                            # kintoneアプリID
STATE="state1"                          # 任意（固定でもOK）
SCOPE="k:app_record:read k:app_record:write"

# 追加したいフィールドをここに全部書く（フィールドコード: value）
RECORD_JSON='{
  "login_id": { "value": "xxxxxxxxx" }
}'

############################################
# 関数
############################################
urlencode_spaces() {
  # scope用：スペースを %20 に変えるだけ（今回の用途ならこれで十分）
  echo "$1" | sed 's/ /%20/g'
}

json_get() {
  # JSONから "key":"value" を抜く（jqなしの簡易パーサ）
  # 使い方: json_get "$json" "access_token"
  local json="$1"
  local key="$2"
  echo "$json" | sed -nE "s/.*\"$key\"[[:space:]]*:[[:space:]]*\"([^\"]*)\".*/\1/p"
}

json_get_number() {
  # JSONから "key":123 を抜く（数字用）
  local json="$1"
  local key="$2"
  echo "$json" | sed -nE "s/.*\"$key\"[[:space:]]*:[[:space:]]*([0-9]+).*/\1/p"
}

die() {
  echo "ERROR: $*" >&2
  exit 1
}

############################################
# 1) 認可URLを表示（ブラウザで開く）
############################################
SCOPE_ENC="$(urlencode_spaces "$SCOPE")"
AUTH_URL="https://${SUBDOMAIN}.cybozu.com/oauth2/authorization?client_id=${CLIENT_ID}&redirect_uri=${REDIRECT_URI}&state=${STATE}&response_type=code&scope=${SCOPE_ENC}"

echo "=============================="
echo "1) ブラウザで以下URLを開いて認可してください"
echo "------------------------------"
echo "$AUTH_URL"
echo "=============================="
echo
echo "認可後、リダイレクト先URLに付く「code」を入力してください。"
echo "例: https://.../callback?code=XXXXX&state=state1"
echo

read -r -p "code を貼り付け（code= の後ろだけ / URL丸ごとでもOK）: " CODE_INPUT

# 入力がURL丸ごとの場合でも code だけ抽出する
CODE="$(echo "$CODE_INPUT" | sed -nE 's/.*[?&]code=([^&]+).*/\1/p')"
if [[ -z "${CODE}" ]]; then
  # code=が無いなら「入力自体がcode」とみなす
  CODE="$CODE_INPUT"
fi

[[ -n "${CODE}" ]] || die "code が取得できませんでした（入力を確認してください）"

############################################
# 2) token 取得
############################################
BASIC="$(printf '%s' "${CLIENT_ID}:${CLIENT_SECRET}" | base64 | tr -d '\n')"

TOKEN_RESP="$(curl -sS -X POST "https://${SUBDOMAIN}.cybozu.com/oauth2/token" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H "Authorization: Basic ${BASIC}" \
  --data-urlencode "grant_type=authorization_code" \
  --data-urlencode "redirect_uri=${REDIRECT_URI}" \
  --data-urlencode "code=${CODE}")"

# エラー判定（errorがあれば終了）
if echo "$TOKEN_RESP" | grep -q '"error"'; then
  echo "---- token endpoint response ----"
  echo "$TOKEN_RESP"
  echo "---------------------------------"
  die "トークン取得に失敗しました（上のエラー内容を確認）"
fi

ACCESS_TOKEN="$(json_get "$TOKEN_RESP" "access_token")"
REFRESH_TOKEN="$(json_get "$TOKEN_RESP" "refresh_token")"
TOKEN_TYPE="$(json_get "$TOKEN_RESP" "token_type")"
EXPIRES_IN="$(json_get_number "$TOKEN_RESP" "expires_in")"
SCOPE_GOT="$(json_get "$TOKEN_RESP" "scope")"

[[ -n "${ACCESS_TOKEN}" ]] || die "access_token の抽出に失敗しました（レスポンス形式が想定外）"

echo
echo "=============================="
echo "2) トークン取得OK"
echo "------------------------------"
echo "token_type  : ${TOKEN_TYPE:-}"
echo "expires_in  : ${EXPIRES_IN:-}"
echo "scope       : ${SCOPE_GOT:-}"
echo "access_token: ${ACCESS_TOKEN}"
echo "refresh_tok : ${REFRESH_TOKEN:-}"
echo "=============================="
echo

############################################
# 3) レコード投入
############################################
RECORD_PAYLOAD="$(cat <<EOF
{
  "app": "${APP_ID}",
  "record": ${RECORD_JSON}
}
EOF
)"

ADD_RESP="$(curl -sS -X POST "https://${SUBDOMAIN}.cybozu.com/k/v1/record.json" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "$RECORD_PAYLOAD")"

if echo "$ADD_RESP" | grep -q '"message"\|"error"'; then
  echo "---- add record response ----"
  echo "$ADD_RESP"
  echo "-----------------------------"
  die "レコード投入に失敗しました（権限/フィールドコード/APP_IDなど確認）"
fi

RECORD_ID="$(json_get "$ADD_RESP" "id")"
[[ -n "${RECORD_ID}" ]] || die "レコードIDの抽出に失敗しました（レスポンス: $ADD_RESP）"

echo "=============================="
echo "3) レコード投入OK"
echo "------------------------------"
echo "record_id: ${RECORD_ID}"
echo "=============================="
echo

############################################
# 4) 作成者確認（$creator）
############################################
GET_RESP="$(curl -sS -X GET "https://${SUBDOMAIN}.cybozu.com/k/v1/record.json?app=${APP_ID}&id=${RECORD_ID}" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}")"

# $creator は JSON のキーとして "$creator" なので、その周辺を抜く
CREATOR_BLOCK="$(echo "$GET_RESP" | tr -d '\n' | sed -nE 's/.*"\$creator"[[:space:]]*:[[:space:]]*\{([^}]*)\}.*/\1/p')"
CREATOR_CODE="$(echo "$CREATOR_BLOCK" | sed -nE 's/.*"code"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/p')"
CREATOR_NAME="$(echo "$CREATOR_BLOCK" | sed -nE 's/.*"name"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/p')"

echo "=============================="
echo "4) 作成者確認"
echo "------------------------------"
if [[ -n "${CREATOR_CODE}" || -n "${CREATOR_NAME}" ]]; then
  echo "creator.code: ${CREATOR_CODE}"
  echo "creator.name: ${CREATOR_NAME}"
else
  echo "作成者情報の抽出に失敗しました。レスポンス全文を表示します:"
  echo "$GET_RESP"
fi
echo "=============================="
echo
echo "完了！"