# Streamline Site Status Report

This report gives you an overview of the packages(modules) you have installed
and tells you whether they are stable and up to date.

It also tells you the following details about a package:

  * Current Version
  * Latest Version
  * Stable
  * Up To Date

## How it works

This report use the composer.lock file to find out what packages you have installed
and what version they are. Once it has this information it uses github's api to
find out the latest version.

## What is classed as unstable

Unstable versions are any of the following:

  * dev-master
  * alpha
  * beta
  * rc
  * dev
