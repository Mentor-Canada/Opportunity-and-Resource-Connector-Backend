set -ex
cd web
patch -p1 < ../patches/logouttoken.patch
patch -p1 < ../patches/jsonapi-skip-entity-validation.patch
patch -p1 < ../patches/jsonapi-increase-pagination-limit.patch
cd ..
patch -p1 < ./patches/drush-postfix-fix.patch
