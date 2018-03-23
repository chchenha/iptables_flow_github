-- phpMyAdmin SQL Dump
-- version 4.4.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-11-18 21:26:42
-- 服务器版本： 5.6.26-log
-- PHP Version: 5.6.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `shadowsocks`
--

-- --------------------------------------------------------

--
-- 表的结构 `ss_iptables_log_201711`
--

CREATE TABLE IF NOT EXISTS `ss_iptables_log_201711` (
  `id` int(10) NOT NULL,
  `file_name` varchar(20) NOT NULL,
  `d` int(10) NOT NULL,
  `port` int(10) NOT NULL,
  `output_tcp_bytes` bigint(20) DEFAULT NULL,
  `output_udp_bytes` bigint(20) DEFAULT NULL,
  `input_tcp_bytes` bigint(20) DEFAULT NULL,
  `input_udp_bytes` bigint(20) DEFAULT NULL,
  `total_out_bytes` bigint(20) DEFAULT NULL,
  `total_in_bytes` bigint(20) DEFAULT NULL,
  `total_bytes` bigint(20) DEFAULT NULL,
  `add_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ss_iptables_log_201711`
--
ALTER TABLE `ss_iptables_log_201711`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_name` (`file_name`,`d`,`port`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ss_iptables_log_201711`
--
ALTER TABLE `ss_iptables_log_201711`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;